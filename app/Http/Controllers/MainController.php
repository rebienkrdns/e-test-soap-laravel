<?php

namespace App\Http\Controllers;

use App\Http\Soap\MainSoapServer;
use KDuma\SoapServer\AbstractSoapServerController;

class MainController extends AbstractSoapServerController
{
    protected function getService(): string
    {
        return MainSoapServer::class;
    }

    protected function getEndpoint(): string
    {
        return route('mainSoapServer');
    }

    protected function getWsdlUri(): string
    {
        return route('mainSoapServer.wsdl');
    }
}
