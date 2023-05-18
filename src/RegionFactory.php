<?php

namespace Fize\Provider\Region;

/**
 * 省市区工厂类
 */
class RegionFactory
{

    /**
     * 新建实例
     * @param string $handler 使用的实际接口名称
     * @param array  $config  配置项
     * @return RegionHandler
     */
    public function create(string $handler, array $config = []): RegionHandler
    {
        $class = '\\' . __NAMESPACE__ . '\\Handler\\' . $handler;
        return new $class($config);
    }
}
