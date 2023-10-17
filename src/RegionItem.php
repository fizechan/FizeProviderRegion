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
    public $pid;

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
}
