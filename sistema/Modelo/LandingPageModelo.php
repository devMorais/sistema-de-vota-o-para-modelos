<?php

namespace sistema\Modelo;

use sistema\Nucleo\Modelo;

class LandingPageModelo extends Modelo
{
    public function __construct()
    {
        parent::__construct('landing_page');
    }

    public function configuracaoAtiva(): ?LandingPageModelo
    {
        return $this->busca('status = 1')->ordem('id DESC')->limite(1)->resultado();
    }
}
