<?php
/**
 * @author samark chaisanguan
 * @email samarkchsngn@gmail.com
 */
namespace Samark\Datatable;

use Illuminate\Pagination\Paginator;
use Samark\Datatable\Contract\ServiceDatatableInterface;

class ServiceDatatable implements ServiceDatatableInterface
{
    /**
     * set column
     * @var array
     */
    protected $columns = [];

    /**
     * set column link
     * @var array
     */
    protected $columnlink = [];

    /**
     * set response
     * @var array
     */
    protected $response = [];

    /**
     * set total entries
     * @var integer
     */
    protected $totalEntries;

    /**
     * set total records
     * @var integer
     */
    protected $recordsTotal;

    /**
     * [$columnDate description]
     * @var array
     */
    protected $columnDate = [
        'created_at',
        'updated_at',
    ];

    /**
     * set column like
     * @var array
     */
    protected $searchColumnLike = [];

    /**
     * set model
     * @var
     */
    protected $model;

    /**
     * set limit
     * @var integer
     */
    protected $limit = 30;

    /**
     * set offset
     * @var integer
     */
    protected $offset;

    /**
     * date citeraias
     * @var array
     */
    protected $searchColumnDate = [
        'date_start' => '>=',
        'date_end' => '<=',
    ];

    /**
     * set column search able
     * @var array
     */
    protected $columnSearch = [];

    /**
     * set mapping column search
     * @var array
     */
    protected $mappingColumnFilter = [
        'draw' => 'draw',
        'start' => 'offset',
        'length' => 'limit',
        'order' => 'order',
    ];

    /**
     * set default order column
     * @var string
     */
    protected $orderBy = 'id';

    /**
     * set default sort type
     * @var string
     */
    protected $sortedBy = 'asc';

    /**
     * set select column
     * @var array
     */
    protected $selectColumn = ['*'];

    /**
     * set filters
     * @var array
     */
    protected $filters;

    /**
     * set model data
     * @var object
     */
    protected $modelData;

    /**
     * params request
     * @var array
     */
    protected $params = [];

    /**
     * set fillable on model
     * @var array
     */
    protected $fillable;

    /**
     * set default draw page
     * @var integer
     */
    protected $draw = 1;

    /**
     * set list column for generate javascript
     * @var array
     */
    protected $listColumn = [
        "processing" => true,
        "serverSide" => true,
        "order" => [
            [7, "desc"],
        ],
        "ajax" => "",
        "orderCellsTop" => true,
        "searching" => true,
        "columns" => [],
    ];

    public function __construct()
    {
        $this->boot();
    }

    /**
     * [boot description]
     * @return [type] [description]
     */
    public function boot()
    {
        $this->model = app($this->model);
    }

    /**
     * [setLimit description]
     * @param [type] $limit [description]
     */
    protected function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * [setOffset description]
     * @param [type] $offset [description]
     */
    protected function setOffset($offset)
    {
        $this->offset = $offset;
    }

    /**
     * [appendColumnView description]
     * @return [type] [description]
     */
    protected function appendColumnView()
    {
        foreach ($this->columns as $column => $type) {
            $data = [
                'name' => $column,
                'type' => $type,
            ];
            $this->listColumn['columns'] = $orderable;
        }
    }

    /**
     * [mappingColumnFilters description]
     * @return [type] [description]
     */
    protected function mappingColumnFilters()
    {
        $filters = array();
        foreach ($this->mappingColumnFilter as $key => $value) {
            if ($key == 'start') {
                $filters[$value] = array_get($request, $key, 0);
            } elseif ($key == 'order') {
                $filters['orderBy'] =
                    array_get($columns, array_get($request, $key . '.0.column', ''), $this->orderByColumn);
                $filters['sortedBy'] =
                    array_get($request, $key . '.0.dir', 'asc');
            } else {
                $filters[$value] = array_get($request, $key, '');
            }
        }
        $this->filters = $filters;
    }

    /**
     * [getRespones description]
     * @return [type] [description]
     */
    protected function getResponse($data)
    {
        return [
            "draw" => $this->draw,
            "recordsTotal" => $this->recordsTotal,
            "recordsFiltered" => $this->totalEntries,
            "data" => $data,
        ];
    }

    /**
     * [getResponeModel description]
     * @return [type] [description]
     */
    protected function getResponeModel()
    {
        $this->response = $this->model->take($this->limit)
            ->skip($this->offset)
            ->orderBy($this->orderBy, $this->sortedBy)
            ->get($this->selectColumn);
        $this->recordsTotal = $this->response->count();
        return $this->response;
    }

    /**
     * [paginateRende description]
     * @return [type] [description]
     */
    protected function paginateRender()
    {
        $currentPage = ($this->offset / $this->limit) + 1;

        Paginator::currentPageResolver(function () use ($currentPage) {
            return $currentPage;
        });

        $results = $this->model->paginate($this->limit, $columns = ['*']);

        return $results;
    }
    /**
     * gen script for blade
     * @return [type] [description]
     */
    protected function genScript()
    {
        return view('datatable.script');
    }

    /**
     * [setFillable description]
     */
    protected function setFillable()
    {
        $this->fillable = $this->model->getFillable();
        return $this->fillable;
    }

    /**
     * [getParamSearchable description]
     * @return [type] [description]
     */
    protected function getParamSearchable($params)
    {
        $filters = [];
        if (!isset($params['columns'])) {
            return $filters;
        }
        foreach ($params['columns'] as $key => $column) {
            if (isset($column['search']['value']) && !empty($column['search']['value'])) {
                $filters[$column['name']] = $column['search']['value'];
            }
        }
        $this->draw = array_get($params, 'draw', 1);
        $this->params = $filters;
        return $filters;
    }

    /**
     * [getOrderBy description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    protected function getOrderBy($params)
    {
        $sortedBy = array_get($params, 'order.0.dir');
        $key = array_get($params, 'order.0.column');
        $orderBy = array_get($params, 'columns.' . $key . '.name');

        $this->orderBy = !empty($orderBy) ? $orderBy : $this->orderBy;
        $this->sortedBy = !empty($sortedBy) ? $sortedBy : $this->sortedBy;
    }

    /**
     * [getLimitOffset description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    protected function getLimitOffset($params)
    {
        $start = array_get($params, 'start');
        $limit = array_get($params, 'length');
        $this->limit = !empty($limit) ? $limit : $this->limit;
        $this->offset = $start == 0 ? $start : $start - 1;
    }

    /**
     * [search description]
     * @param  [type] $filter [description]
     * @return [type]         [description]
     */
    protected function search($filter)
    {
        $fillable = !empty($this->fillable) ? $this->fillable : $this->setFillable();

        $filter = array_filter($filter);
        $params = array();

        foreach ($filter as $key => $value) {
            if (in_array($key, $fillable) || in_array($key, $this->searchColumnDate)) {
                $params[$key] = $value;
            }
        }

        $this->searchColumnDates($params);
        $this->searchColumnLikes($params);

        foreach ($params as $key => $value) {
            $this->model = $this->model->where(function ($query) use ($key, $value) {
                $query->where($key, $value);
            });
        }

        $this->totalEntries = $this->model->count();
    }

    /**
     * [searchColumnDates description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    protected function searchColumnDates(&$params)
    {
        foreach ($params as $key => $value) {
            if (array_key_exists($key, $this->searchColumnDate)) {
                $this->model = $this->model->where(function ($query) use ($key, $value) {
                    $query->where($key, $this->searchColumnDate[$key], $value);
                });
                unset($params[$key]);
            }
        }
    }

    /**
     * [searchColumnDates description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    protected function searchColumnLikes(&$params)
    {
        foreach ($params as $key => $value) {
            if (in_array($key, $this->searchColumnLike)) {
                $this->model = $this->model->where(function ($query) use ($key, $value) {
                    $query->where($key, 'like', '%' . $value . '%');
                });
                unset($params[$key]);
            }
        }
    }

    /**
     * [serachOrderBy description]
     * @return [type] [description]
     */
    protected function serachOrderBy()
    {
        $this->model = $this->model->orderBy($this->orderBy, $this->sortedBy);
        return $this;
    }

}
