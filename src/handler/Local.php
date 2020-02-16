<?php

namespace fize\provider\region\handler;

use SQLite3;
use fize\provider\region\RegionHandler;
use fize\provider\region\RegionItem;

/**
 * 本地数据
 */
class Local extends RegionHandler
{

    /**
     * @var SQLite3 数据库
     */
    private $db;


    /**
     *  构造
     * @param array $config 配置
     */
    public function __construct(array $config = null)
    {
        parent::__construct($config);
        $this->db = new SQLite3(dirname(dirname(__DIR__)) . "/data/region.sqlite3", SQLITE3_OPEN_READWRITE);
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        $this->db->close();
    }

    /**
     * 根据编码获取完整信息
     * 返回一个长度为3的数组，依次是省市区，为null表示没有指定该数据
     * @param int $id 编码
     * @return RegionItem[]|null[]
     */
    public function get($id)
    {
        $item1 = $this->getOne($id);
        if (is_null($item1)) {
            return [null, null, null];
        }
        if (empty($item1->parentId)) {
            return [null, null, $item1];
        }
        $item2 = $this->getOne($item1->parentId);
        if (empty($item2->parentId)) {
            return [null, $item2, $item1];
        }
        $item3 = $this->getOne($item2->parentId);
        return [$item3, $item2, $item1];
    }

    /**
     * 获取省列表
     * @return RegionItem[]
     */
    public function getProvince()
    {
        return $this->getList(0);
    }

    /**
     * 根据省编码返回市列表
     * @param int $provinceId 省编码
     * @return RegionItem[]
     */
    public function getCity($provinceId)
    {
        return $this->getList($provinceId);
    }

    /**
     * 根据市编码返回区列表
     * @param int $cityId 市编码
     * @return RegionItem[]
     */
    public function getArea($cityId)
    {
        return $this->getList($cityId);
    }

    /**
     * 根据ID返回项
     * @param int $id ID
     * @return RegionItem|null 未找到返回null
     */
    private function getOne($id)
    {
        $row = $this->db->querySingle("SELECT * FROM region WHERE id = {$id}", true);
        if (empty($row)) {
            return null;
        }
        $item = new RegionItem();
        $item->id = $row['id'];
        $item->parentId = $row['parentid'];
        $item->level = $row['level'];
        $item->name = $row['areaname'];
        $item->shortName = $row['shortname'];
        $item->longitude = $row['lng'];
        $item->latitude = $row['lat'];
        return $item;
    }

    /**
     * 获取指定父级下的地区列表
     * @param int $parentid 父级ID
     * @return RegionItem[]
     */
    private function getList($parentid)
    {
        $result = $this->db->query("SELECT * FROM region WHERE parentid = {$parentid} ORDER BY sort ASC");

        $items = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $item = new RegionItem();
            $item->id = $row['id'];
            $item->parentId = $row['parentid'];
            $item->level = $row['level'];
            $item->name = $row['areaname'];
            $item->shortName = $row['shortname'];
            $item->longitude = $row['lng'];
            $item->latitude = $row['lat'];
            $items[] = $item;
        }
        return $items;
    }
}
