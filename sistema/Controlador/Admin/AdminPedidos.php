<?php

namespace sistema\Controlador\Admin;

use sistema\Nucleo\Helpers;
use sistema\Modelo\PedidoModelo;

/**
 * Classe AdminPedidos
 * Gerencia a listagem de pedidos no painel administrativo
 */
class AdminPedidos extends AdminControlador
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Exibe a tela de listagem de pedidos
     */
    public function listar(): void
    {
        $pedidoModelo = new PedidoModelo();

        // Contadores para o topo da página
        $total = [
            'pedidos' => $pedidoModelo->busca()->total(),
            'pagos'   => $pedidoModelo->busca("status = 'PAGO'")->total(),
            'aguardando' => $pedidoModelo->busca("status = 'AGUARDANDO'")->total(),
            'erro'    => $pedidoModelo->busca("status = 'ERRO'")->total(),
        ];

        echo $this->template->renderizar('pedidos/listar.html', [
            'total' => $total
        ]);
    }

    /**
     * Fornece os dados para o Datatable (AJAX)
     */
    public function datatable(): void
    {
        $datatable = $_REQUEST;
        $datatable = filter_var_array($datatable, FILTER_SANITIZE_SPECIAL_CHARS);

        $limite = $datatable['length'];
        $offset = $datatable['start'];
        $busca  = $datatable['search']['value'];

        // Colunas da tabela para ordenação
        $colunas = [
            0 => 'id',
            1 => 'cliente_nome',
            2 => 'cliente_cpf',
            3 => 'valor_subtotal',
            4 => 'valor_taxa',
            5 => 'valor_total',
            6 => 'status',
            7 => 'cadastrado_em'
        ];

        $ordem = " " . $colunas[$datatable['order'][0]['column']] . " ";
        $ordem .= " " . $datatable['order'][0]['dir'] . " ";

        $pedidos = new PedidoModelo();

        if (empty($busca)) {
            $pedidos->busca()->ordem($ordem)->limite($limite)->offset($offset);
            $total = (new PedidoModelo())->busca()->total();
        } else {
            // Busca por nome, cpf ou email
            $pedidos->busca("cliente_nome LIKE '%{$busca}%' OR cliente_cpf LIKE '%{$busca}%' OR cliente_email LIKE '%{$busca}%' ")
                ->limite($limite)
                ->offset($offset);
            $total = $pedidos->total();
        }

        $dados = [];

        if ($pedidos->resultado(true)) {
            foreach ($pedidos->resultado(true) as $pedido) {
                $dados[] = [
                    $pedido->id,
                    $pedido->cliente_nome,
                    $pedido->cliente_cpf, // Index 2
                    Helpers::formatarValor($pedido->valor_subtotal), // Index 3
                    Helpers::formatarValor($pedido->valor_taxa),     // Index 4
                    Helpers::formatarValor($pedido->valor_total),    // Index 5
                    $pedido->status,      // Index 6
                    date('d/m/Y H:i', strtotime($pedido->cadastrado_em)), // Index 7
                    // Botão Visualizar (Index 8)
                    '<a href="' . Helpers::url('admin/pedidos/ver/' . $pedido->id) . '" class="btn btn-sm btn-secondary" tooltip="tooltip" title="Ver Detalhes"><i class="fa-solid fa-eye"></i></a>'
                ];
            }
        }

        $retorno = [
            "draw" => $datatable['draw'],
            "recordsTotal" => $total,
            "recordsFiltered" => $total,
            "data" => $dados
        ];

        echo json_encode($retorno);
    }

    /**
     * Exibe os detalhes de um pedido
     * @param int $id
     * @return void
     */
    public function ver(int $id): void
    {
        // ADICIONE ESTA LINHA ABAIXO PARA O VS CODE ENTENDER:
        /** @var \sistema\Modelo\PedidoModelo $pedido */
        $pedido = (new PedidoModelo())->buscaPorId($id);

        if (!$pedido) {
            $this->mensagem->alerta('Pedido não encontrado!')->flash();
            Helpers::redirecionar('admin/pedidos/listar');
        }

        // Agora o VS Code sabe que $pedido é da classe PedidoModelo e vai reconhecer o método post()
        $candidata = $pedido->post();

        echo $this->template->renderizar('pedidos/ver.html', [
            'pedido' => $pedido,
            'candidata' => $candidata
        ]);
    }
}
