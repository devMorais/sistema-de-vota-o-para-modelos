<?php

namespace sistema\Modelo;

use sistema\Nucleo\Modelo;
use sistema\Nucleo\Conexao;


class PedidoModelo extends Modelo
{
    public function __construct()
    {
        parent::__construct('pedidos');
    }

    public function confirmarPagamento(): bool
    {
        $db = Conexao::getInstancia();
        $db->beginTransaction();

        try {
            $this->status = 'PAGO';
            $this->pago_em = date('Y-m-d H:i:s');

            $this->metodo_pagamento = $this->metodo_pagamento ?? 'ASAAS';

            if (!$this->salvar()) {
                throw new \Exception("Falha ao salvar status do pedido.");
            }

            $post = $this->post();
            if ($post) {
                $post->adicionarVotos($this->total_votos);
                $post->adicionarReceita((float)$this->valor_total);
            }

            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            error_log("Erro Pedido #{$this->id}: " . $e->getMessage());
            return false;
        }
    }

    public function financeiroPorPost(int $postId): ?object
    {
        $colunas = "SUM(valor_total) as bruto, SUM(valor_taxa) as taxas, SUM(valor_subtotal) as lucro";
        $termos = "post_id = :id AND status = 'PAGO'";
        $parametros = "id={$postId}";

        return $this->busca($termos, $parametros, $colunas)->resultado(); //
    }

    public function post(): ?PostModelo
    {
        if ($this->post_id) {
            return (new PostModelo())->buscaPorId($this->post_id); //
        }
        return null;
    }

    public function pacote(): ?PacoteModelo
    {
        if ($this->pacote_id) {
            return (new PacoteModelo())->buscaPorId($this->pacote_id);
        }
        return null;
    }
}
