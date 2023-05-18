<?php


namespace Fize\Provider\Region;

/**
 * 区域项
 */
final class RegionItem
{

    /**
     * @var int 行政区划代码
     */
    public $id;

    /**
     * @var int 父级行政区划代码
     */
    public $parentId;

    /**
     * @var string 名称
     */
    public $name;

    /**
     * @var int 级别
     */
    public $level;

    /**
     * @var string 简称
     */
    public $shortName;

    /**
     * @var float 经度
     */
    public $longitude;

    /**
     * @var float 纬度
     */
    public $latitude;
}
