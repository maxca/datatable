<?php
/**
 * @author samark chaisanguan
 * @email samarkchsngn@gmail.com
 */
namespace Samark\Datatable;

class ServiceDatatableWrap extends ServiceDatatable
{
    /**
     * set instance name
     * @var string
     */
    protected $instanceName;

    /**
     * set column display view
     * @var array
     */
    protected $columnDisplay = [];

    /**
     * set default lang
     * @var string
     */
    protected $lang = 'th';

    /**
     * set default need fake id.
     * @var boolean
     */
    protected $needFakeId = false;

    /**
     * set column need translate
     * @var array
     */
    protected $columnLang = [

    ];
    /**
     * set action button
     * @var array
     */
    protected $actionButton = [];

    public function __construct()
    {
        parent::__construct();
    }
    /**
     * [genParams description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function genParams($params)
    {
        parent::getParamSearchable($params);
        parent::getOrderBy($params);
        parent::getLimitOffset($params);
    }

    /**
     * [serach description]
     * @return [type] [description]
     */
    public function excute()
    {
        parent::search($this->params);
        $data = parent::getResponeModel();

        return $this->genAjaxFormat($data);
    }

    /**
     * [genAjaxFormat description]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    protected function genAjaxFormat($data)
    {
        $result = array();
        $data = !empty($data) ? $data->toArray() : [];
        $offset = ($this->offset == 0) ? 1 : $this->offset + 2;

        foreach ($data as $key => $items) {

            foreach ($items as $field => $value) {

                if (array_key_exists($field, $this->columnlink)) {
                    $name = $this->getTranslate($items[$this->columnlink[$field]]);
                    $alias = $this->columnlinkRoute[$field];
                    $route = route($alias, [$field => $value]);
                    $result[$key][] = "<a href='" . $route . "'>" . $name . "</a>";

                } elseif (in_array($field, $this->columnLang)) {
                    $result[$key][] = $this->getTranslate($value);
                } elseif (in_array($field, $this->columnDisplay)) {
                    if ($field == 'id' && $this->needFakeId == true) {
                        $result[$key][$field] = $offset++;
                    } else {
                        $result[$key][$field] = $value;
                    }

                }
            }
            $result[$key] = $this->sortIndexBySetting($result[$key]);
        }
        $this->genActionButton($result);
        return parent::getResponse($result);
    }

    /**
     * [sortIndexBySetting description]
     * @param  [type] $key   [description]
     * @param  array  $items [description]
     * @return [type]        [description]
     */
    protected function sortIndexBySetting($items = array())
    {
        $result = array();
        foreach ($this->columnDisplay as $key => $column) {
            if (array_key_exists($column, $items)) {
                $result[$key] = $items[$column];
            }
        }
        return $result;
    }

    /**
     * [genActionButton description]
     * @param  [type] &$result [description]
     * @return [type]          [description]
     */
    protected function genActionButton(&$result)
    {
        if (!empty($this->actionButton)) {
            foreach ($result as $key => $items) {
                $result[$key] = $items;
                $id = array_get($items, '0');

                if (isset($this->actionButton['edit'])) {
                    $click = "onclick='edit{$this->instanceName}($id)'";
                    $btn = '<button type="button" onlc class="btn btn-default btn-sm btn-edit" ' . $click . '>
                                <i class="fa fa-edit"></i>
                            </button>';
                }
                if (isset($this->actionButton['delete'])) {
                    $click = "onclick='delete{$this->instanceName}($id)'";
                    $btn .= '<button type="button" class="btn btn-danger  btn-sm btn-delete" ' . $click . '>
                                <i class="fa fa-trash-o"></i>
                            </button>';
                }
                $result[$key][] = $btn;
            }
        }
    }
    /**
     * [getTranslate description]
     * @param  [type] $column [description]
     * @return [type]         [description]
     */
    protected function getTranslate($column)
    {
        return is_array($column) ? array_get($column, $this->lang) : $column;
    }

}
