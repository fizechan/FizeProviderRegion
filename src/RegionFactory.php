<?php

namespace Fize\Provider\Region;

/**
 * 行政区划工厂类
 */
class RegionFactory
{

    /**
     * 新建实例
     * @param string $handler 接口名称
     * @param array  $config  配置项
     * @return RegionHandlerInterface
     */
    public function create(string $handler, array $config = []): RegionHandlerInterface
    {
        $class = '\\' . __NAMESPACE__ . '\\Handler\\' . $handler;
        return new $class($config);
    }
}
