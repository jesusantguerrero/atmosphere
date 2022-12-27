<?php

namespace Freesgen\Atmosphere\Http;

use Illuminate\Support\Facades\Schema;

trait Querify
{

    private $modelQuery;
    private $whereRaw;
    private $request;
    protected $authorizedUser = true;
    protected $authorizedTeam = true;
    private $serverParams;

    public function getModelQuery($request, $id=null, $extendFunction=null)
    {
        $this->request = $request;
        $queryParams = $request->query();
        $limit = $queryParams['limit'] ?? null;
        $page = $queryParams['page'] ?? null;
        $search = $queryParams['search'] ?? null;
        $sorts =  $queryParams['sort'] ?? null;
        $relationships = $queryParams['relationships'] ?? null;
        $filters = $queryParams['filter'] ?? [];

        $this->modelQuery = $this->model::query();
        $this->getRelationships($relationships);
        $this->getSorts($sorts);
        $this->getFilters($filters);
        $this->getSearch($search);

        $this->serverParams = [
            'filters' => $filters,
            'limit' => $limit,
            'search' => $search,
            'sorts' => $sorts,
            'relationships' => $relationships
        ];

        if ($extendFunction) {
          $extendFunction($this->modelQuery, $queryParams);
        }

        if ($this->whereRaw) {
            $this->modelQuery->whereRaw($this->whereRaw);
        }

        if ($id) {
          return $this->modelQuery->where(["id" => $id])->get();
        }

        if ($this->authorizedUser) {
           $this->modelQuery->where(["user_id" => $request->user()->id]);
        }

        if ($this->authorizedTeam) {
            $this->modelQuery->where(["team_id" => $request->user()->current_team_id]);
         }

        return $this->getPaginate($limit, $page);
    }

    public function getServerParams() {
        return $this->serverParams;
    }

    private function getSearch($search)
    {

        if (!$search) return '';
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
        $this->whereRaw = $whereRaw;
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
                if ($valueField  = explode('.', $value)) {
                    if (count($valueField) > 1 && $valueField[0] == 'user') {
                        $userField = $valueField[1];
                        $value = $this->request->user()->$userField;
                    } else if (Schema::hasColumn($this->model->getTable(),$filter)) {
                        $filter = $this->model->getTable().".".$filter;
                    } else {
                        $filter = '';
                    }
                }

                $filter && $this->addFilter($filter, $value);
            }
        }

        return $this->filters;
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

    private function getPaginate($limit, $page)
    {
        if ($limit && $page) {
            return $this->modelQuery->paginate($limit, $page);
        } else if ($limit) {
            return $this->modelQuery->limit($limit);
        }

        return $this->modelQuery->get();
    }

    private function getSorts($externalSorts)
    {
        $sorts = implode(',', $this->sorts) . $externalSorts;
        if ($sorts) {
            $sorts = $this->splitAndTrim($sorts);
            foreach ($sorts as $sort) {
                $direction = strpos($sort, "-") !== False ? "DESC" : "ASC";
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
