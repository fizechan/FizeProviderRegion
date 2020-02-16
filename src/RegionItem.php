<?php


namespace fize\provider\region;

/**
 * 区域项
 */
final class RegionItem
{

    /**
     * @var int 编码
     */
    public $id;

    /**
     * @var int 父级编码
     */
    public $parentId;

    /**
     * @var int 级别
     */
    public $level;

    /**
     * @var string 名称
     */
    public $name;

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
