<?php
/*
 * core.php
 *
 * Copyright 2014 geniv
 *
 */

namespace Geniv\Goodflow;

/**
 * hlavni trida s nejpouzivanenejsimi statickymi metodami
 * - nevytvoritelna (abstraktni)
 *
 * @package unstable
 * @author geniv
 * @version 1.0
 */
abstract class Core {

    public function getBasePath(Nette\Http\Request $request) {
        return rtrim($request->getUrl()->getBasePath(), '/');
    }
}