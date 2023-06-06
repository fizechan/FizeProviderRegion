<?php

namespace Fize\Provider\Region\Handler;

use Fize\Provider\Region\RegionHandler;
use Fize\Provider\Region\RegionItem;
use SQLite3;

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
        $this->db = new SQLite3(dirname(__DIR__, 2) . "/data/Local.sqlite3", SQLITE3_OPEN_READONLY);
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
    public function get(int $id): array
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
    public function getProvinces(): array
    {
        return $this->getList(0);
    }

    /**
     * 根据省编码返回市列表
     * @param int $provinceId 省编码
     * @return RegionItem[]
     */
    public function getCitys(int $provinceId): array
    {
        return $this->getList($provinceId);
    }

    /**
     * 根据市编码返回区列表
     * @param int $cityId 市编码
     * @return RegionItem[]
     */
    public function getAreas(int $cityId): array
    {
        return $this->getList($cityId);
    }

    /**
     * 获取完整名称
     * @param int    $id        编码
     * @param string $separator 间隔符
     * @param int    $adjust    调整方式：0-不调整；1-去除【市辖区、县】；
     * @return string
     */
    public function getFullName(int $id, string $separator = '', int $adjust = 0): string
    {
        $area = $this->db->querySingle("SELECT * FROM region WHERE id = {$id}", true);
        if (empty($area)) {
            return '';
        }
        $full_name = $area['name'];
        $city = $this->db->querySingle("SELECT * FROM region WHERE id = {$area['pid']}", true);
        if (substr((string)$city['id'], -2) != '00' || $adjust == 0) {
            $full_name = $city['name'] . $separator . $full_name;
        }
        $province = $this->db->querySingle("SELECT * FROM region WHERE id = {$city['pid']}", true);
        $full_name = $province['name'] . $separator . $full_name;
        return $full_name;
    }

    /**
     * 根据ID返回项
     * @param int $id ID
     * @return RegionItem|null 未找到返回null
     */
    private function getOne(int $id)
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
    private function getList(int $parentid): array
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
