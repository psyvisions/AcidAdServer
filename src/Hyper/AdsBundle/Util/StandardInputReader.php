<?php

namespace Hyper\AdsBundle\Util;

class StandardInputReader
{
    // @codeCoverageIgnoreStart
    public function getStandardInput()
    {
        return file_get_contents('php://input');
    }
    // @codeCoverageIgnoreEnd
}