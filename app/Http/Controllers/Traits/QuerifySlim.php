<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QuerifySlim
{
    private $modelQuery;

    private $whereRaw;

    private $request;

    protected $authorizedUser = true;

    protected $authorizedTeam = true;

    protected $searchable = ['id'];

    protected $validationRules = [];

    protected $sorts = [];

    protected $includes = [];

    protected $appends = [];

    protected $filters = [];

    protected $responseType = 'inertia';

    protected $resourceName;

    protected $tableName;

    public function __construct(mixed $params)
    {
        $this->includes = $params['includes'];
        $this->sorts = $params['sorts'];
        $this->filters = $params['filters'];
    }

    public function getModelQuery($request, $tableName, $extendFunction = null)
    {
        $this->request = $request;
        $this->tableName = $tableName;
        $queryParams = $request->query();
        $params = $this->getServerParams();

        $this->modelQuery = DB::table($tableName);
        $this->getRelationships($params['relationships']);
        $this->getSorts($params['sorts']);
        $this->getFilters($params['filters']);
        $this->getSearch($params['search']);

        $this->serverParams = $params;

        if ($extendFunction) {
            $extendFunction($this->modelQuery, $queryParams);
        }

        if ($this->whereRaw) {
            $this->modelQuery->whereRaw($this->whereRaw);
        }

        if ($this->authorizedUser) {
            $this->modelQuery->where([$this->tableName.'.user_id' => $request->user()->id]);
        }

        if ($this->authorizedTeam) {
            $this->modelQuery->where([$this->tableName.'.team_id' => $request->user()->current_team_id]);
        }

        return $this->getPaginate($params['limit'], $params['page']);
    }

    public function getServerParams()
    {
        $queryParams = request()->query();
        $limit = $queryParams['limit'] ?? null;
        $page = $queryParams['page'] ?? null;
        $search = $queryParams['search'] ?? null;
        $sorts = $queryParams['sort'] ?? null;
        $relationships = $queryParams['relationships'] ?? null;
        $filters = $queryParams['filter'] ?? [];

        return $this->serverParams = [
            'filters' => $filters,
            'page' => $page,
            'limit' => $limit,
            'search' => $search,
            'sorts' => $sorts,
            'relationships' => $relationships,
        ];
    }

    private function getSearch($search)
    {

        if (! $search) {
            return '';
        }
        $whereRaw = '';
        // handle search
        foreach ($this->searchable as $field) {
            //  add search fields to where clause
            if (! $whereRaw) {
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
                    $this->modelQuery->leftJoin($relation, $this->tableName.'.'.$attrs['pk'], $relation.'.'.$attrs['fk']);
                    $fullRelations[] = $relation;
                } else {
                    $fullRelations[] = $attrs;
                }
            }

            if ($relationships && count($relationships = explode(',', $relationships))) {
                $fullRelations = array_unique(array_merge($relationships, $fullRelations));
            }
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
                    } elseif (Schema::hasColumn($this->tableName, $filter)) {
                        $filter = $this->tableName.'.'.$filter;
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
        $optionalValues = null;
        $disableSecondWhere = null;

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
            $where = 'where';
            if ($index && ! $disableSecondWhere) {
                $where = 'orWhere';
            }

            $isBetween = strpos($optionalValue, '~');

            if ($isBetween != false) {
                $betweenArgs = $this->splitAndTrim($optionalValue, '~');
                $methodName = $where.'Between';
                $this->modelQuery->$methodName($field, $betweenArgs);

                return;
            }

            $posValue = substr($optionalValue, 1);
            switch (substr($optionalValue, 0, 1)) {
                case '>':
                    $this->modelQuery->$where($field, '>', $posValue);
                    break;
                case '<':
                    $this->modelQuery->$where($field, '<', $posValue);
                    break;
                case '%':
                    $this->modelQuery->$where($field, 'LIKE', $posValue);
                    break;
                case '~':
                    $methodName = $where.'Not';
                    $this->modelQuery->$methodName($field, $posValue);
                    break;
                case '$':
                    $methodName = $where.'Null';
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
        } elseif ($limit) {
            $this->modelQuery->limit($limit);
        }

        return $this->modelQuery->get();
    }

    private function getSorts($externalSorts)
    {
        $sorts = implode(',', $this->sorts).$externalSorts;
        if ($sorts) {
            $sorts = $this->splitAndTrim($sorts);
            foreach ($sorts as $sort) {
                $direction = strpos($sort, '-') !== false ? 'DESC' : 'ASC';
                $sort = $direction == 'ASC' ? $sort : substr($sort, 1);
                $this->modelQuery->orderBy($sort, $direction);
            }
        }
    }

    private function splitAndTrim($text, $separator = ',')
    {
        $values = explode($separator, $text);

        return array_map(function ($value) {
            return trim($value);
        }, $values);
    }
}
