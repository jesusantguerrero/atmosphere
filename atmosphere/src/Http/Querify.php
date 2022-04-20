<?php

namespace Atmosphere\Http;

trait Querify
{

    private $modelQuery;
    private $whereRaw;
    private $request;
    protected $authorizedUser = true;

    public function getModelQuery($request, $id=null, $extendFunction=null)
    {
        $this->request = $request;
        $queryParams = $request->query();
        $limit = isset($queryParams['limit']) ? $queryParams['limit'] : null;
        $search = isset($queryParams['search']) ? $queryParams['search'] : null;
        $sorts = isset($queryParams['sort']) ? $queryParams['sort'] : null;
        $relationships = isset($queryParams['relationships']) ? $queryParams['relationships'] : null;
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];

        $this->modelQuery = $this->model::query();
        $this->whereRaw = $this->getSearch($search);
        $this->getRelationships($relationships);
        $this->getFilters($filters);
        $this->getSorts($sorts);

        if ($extendFunction) {
          $extendFunction($this->modelQuery, $queryParams);
        }

        if ($id) {
          return $this->modelQuery->where(["id" => $id])->get();
        }

        if ($this->authorizedUser) {
           $this->modelQuery->where(["user_id" => $request->user()->id]);
        }


        return $this->getPaginate($limit);
    }

    private function getSearch($search)
    {
        $whereRaw = '';
         // handle search
         foreach ($this->searchable as $field) {
            //  add search fields to where clause
            if (!$whereRaw) {
                $whereRaw .= "$field like '%$search%'";
            } else {
                $whereRaw .= " or $field like '%$search%'";
            }
        }

        return $whereRaw;
    }

    private function getRelationships($relationships)
    {
        if ($this->includes && count($includes = $this->includes)) {
            $fullRelations = [];
            foreach ($includes as $relation => $attrs) {
                if (is_array($attrs)) {
                    $this->modelQuery->leftJoin($relation, $this->model->getTable().".".$attrs['pk'], $relation.".".$attrs['fk']);
                    $fullRelations[] = $relation;
                } else {
                    $fullRelations[] = $attrs;
                }
            }

            if ($relationships && count($relationships = explode(',', $relationships))) {
                $fullRelations = array_unique(array_merge($relationships, $fullRelations));
            }
            $this->modelQuery->with($fullRelations);
        }
    }

    private function getFilters($filters)
    {
        $this->filters = array_merge($this->filters, $filters);

        if (count($this->filters)) {
            foreach ($this->filters as $filter => $value) {
                if ($valueField = explode('.', $value)) {
                    if (count($valueField) > 1 && $valueField[0] == 'user') {
                        $userField = $valueField[1];
                        $value = $this->request->user()->$userField;
                    } else {
                        $filter = $this->model->getTable().".".$filter;
                    }
                }
                $this->addFilter($filter, $value);
            }
        }
    }

    private function addFilter($field, $value)
    {
        $optionalValues= null;
        $disableSecondWhere=null;

        switch (gettype($value)) {
          case 'string':
            $optionalValues = $this->splitAndTrim($value);
            break;
          case 'array':
                $optionalValues = $value;
                $disableSecondWhere = true;
              break;
          default:
                $optionalValues = [];
           break;
        }

        foreach ($optionalValues as $index => $optionalValue) {
          $where = "where";
          if ($index && !$disableSecondWhere) {
            $where = "orWhere";
          }

          $isBetween = strpos($optionalValue, "~");

          if ($isBetween != false) {
            $betweenArgs = $this->splitAndTrim($optionalValue, '~');
            $methodName = $where."Between";
            $this->modelQuery->$methodName($field, $betweenArgs);
            return;
          }

          $posValue =  substr($optionalValue, 1);

          switch (substr($optionalValue, 0, 1)) {
            case '>':
              $this->modelQuery->$where($field, ">", $posValue);
              break;
            case '<':
              $this->modelQuery->$where($field, "<", $posValue);
              break;
            case '%':
              $this->modelQuery->$where($field,'LIKE', $posValue);
              break;
            case '~':
              $methodName = $where."Not";
              $this->modelQuery->$methodName($field, $posValue);
              break;
            case '$':
              $methodName = $where."Null";
              $this->modelQuery->$methodName($field);
              break;
            default:
              $this->modelQuery->$where($field, $optionalValue);
              break;
          }
        }
    }

    private function getPaginate($limit)
    {
        return $this->modelQuery->paginate($limit);
    }

    private function getSorts($sorts)
    {
        if ($sorts) {
            $sorts = $this->splitAndTrim($sorts);
            foreach ($sorts as $sort) {
                $direction = strpos($sort, "-") ? "DESC" : "ASC";
                $sort = $direction == "ASC" ? $sort : substr($sort, 1);
                $this->modelQuery->orderBy($sort, $direction);
            }
          }
    }

    private function splitAndTrim($text, $separator = ',') {
        $values = explode($separator, $text);
        return array_map(function($value) {
            return trim($value);
        }, $values);
      }
}
