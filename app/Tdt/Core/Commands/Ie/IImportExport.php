<?php

namespace Tdt\Core\Commands\Ie;

/**
 * Interface for import/export containers
 * @copyright (C) 2011, 2014 by OKFN Belgium vzw/asbl
 * @license AGPLv3
 * @author Michiel Vancoillie <michiel@okfn.be>
 */

interface IImportExport
{

    public static function import($data);
    public static function export($identifier = null);

}