<?php

namespace sistema\Controlador\Admin;

use sistema\Nucleo\Helpers;
use sistema\Modelo\PacoteModelo;
use sistema\Suporte\XDebug;

/**
 * Classe AdminPacotes
 *
 * @author Fernando Aguiar
 */
class AdminPacotes extends AdminControlador
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Método datatables
     * @return void
     */
    public function datatable(): void
    {
        $datatable = $_REQUEST;
        $datatable = filter_var_array($datatable, FILTER_SANITIZE_SPECIAL_CHARS);

        $limite = $datatable['length'];
        $offset = $datatable['start'];
        $busca = $datatable['search']['value'];

        $colunas = [
            0 => 'id',
            2 => 'titulo',
            3 => 'quantidade',
            4 => 'valor',
            5 => 'taxa',
            6 => 'status',
        ];

        $ordem = " " . $colunas[$datatable['order'][0]['column']] . " ";
        $ordem .= " " . $datatable['order'][0]['dir'] . " ";

        $pctVotos = new PacoteModelo();

        if (empty($busca)) {
            $pctVotos->busca()->ordem($ordem)->limite($limite)->offset($offset);
            $total = (new PacoteModelo())->busca(null, 'COUNT(id)', 'id')->total();
        } else {
            $pctVotos->busca("id LIKE '%{$busca}%' OR titulo LIKE '%{$busca}%' ")->limite($limite)->offset($offset);
            $total = $pctVotos->total();
        }

        $dados = [];

        if ($pctVotos->resultado(true)) {
            foreach ($pctVotos->resultado(true) as $voto) {
                $dados[] = [
                    $voto->id,
                    $voto->titulo,
                    Helpers::formatarNumero($voto->quantidade),
                    Helpers::formatarValor($voto->valor),
                    Helpers::formatarValor($voto->taxa),
                    $voto->status
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
     * Lista ingressos
     * @return void
     */
    public function listar(): void
    {
        $pctVotos = new PacoteModelo();
        echo $this->template->renderizar('ingressos/listar.html', [
            'total' => [
                'ingressos' => $pctVotos->busca(null, 'COUNT(id)', 'id')->total(),
                'ingressosAtivo' => $pctVotos->busca('status = :s', 's=1 COUNT(status))', 'status')->total(),
                'ingressosInativo' => $pctVotos->busca('status = :s', 's=0 COUNT(status)', 'status')->total(),
            ]
        ]);
    }

    /**
     * Cadastra ingressos
     * @return void
     */
    public function cadastrar(): void
    {
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($dados)) {

            if ($this->validarDados($dados)) {
                $pctVotos = new PacoteModelo();

                $pctVotos->titulo = $dados['titulo'];
                $pctVotos->quantidade = (int) Helpers::limparNumero($dados['quantidade']);
                $pctVotos->valor = Helpers::limparValor($dados['valor']);
                $pctVotos->taxa = Helpers::limparValor($dados['taxa']);
                $pctVotos->status = $dados['status'];

                if ($pctVotos->salvar()) {
                    $this->mensagem->sucesso('Pacote de ingressos cadastrado com sucesso')->flash();
                    Helpers::redirecionar('admin/ingressos/listar');
                } else {
                    $this->mensagem->erro($pctVotos->erro())->flash();
                    Helpers::redirecionar('admin/ingressos/listar');
                }
            }
        }

        echo $this->template->renderizar('ingressos/formulario.html', [
            'ingresso' => $dados
        ]);
    }

    /**
     * Edita Pacote de ingresso pelo ID
     * @param int $id
     * @return void
     */
    public function editar(int $id): void
    {
        $pctVotos = (new PacoteModelo())->buscaPorId($id);
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (isset($dados)) {
            if ($this->validarDados($dados)) {
                $pctVotos = (new PacoteModelo())->buscaPorId($id);

                $pctVotos->titulo = $dados['titulo'];
                $pctVotos->quantidade = (int) Helpers::limparNumero($dados['quantidade']);
                $pctVotos->valor = Helpers::limparValor($dados['valor']);
                $pctVotos->taxa = Helpers::limparValor($dados['taxa']);
                $pctVotos->status = $dados['status'];

                if ($pctVotos->salvar()) {
                    $this->mensagem->sucesso('Pacote de ingressos atualizado com sucesso')->flash();
                    Helpers::redirecionar('admin/ingressos/listar');
                } else {
                    $this->mensagem->erro($pctVotos->erro())->flash();
                    Helpers::redirecionar('admin/ingressos/listar');
                }
            }
        }

        echo $this->template->renderizar('ingressos/formulario.html', [
            'ingresso' => $pctVotos,
        ]);
    }

    /**
     * Valida os dados do formulário
     * @param array $dados
     * @return bool
     */
    public function validarDados(array $dados): bool
    {
        if (empty($dados['titulo'])) {
            $this->mensagem->alerta('Escreva um nome para o Pacote de ingresso!')->flash();
            return false;
        }
        if (empty($dados['quantidade'])) {
            $this->mensagem->alerta('Quantidade não pode ficar vazia!')->flash();
            return false;
        }
        if (empty($dados['valor'])) {
            $this->mensagem->alerta('O valor não pode ficar vazio')->flash();
            return false;
        }

        return true;
    }

    /**
     * Deleta posts por ID
     * @param int $id
     * @return void
     */
    public function deletar(int $id): void
    {
        if (is_int($id)) {
            $pctVotos = (new PacoteModelo())->buscaPorId($id);
            if (!$pctVotos) {
                $this->mensagem->alerta('O pacote de votos que você está tentando deletar não existe!')->flash();
                Helpers::redirecionar('admin/ingressos/listar');
            } else {
                if ($pctVotos->deletar()) {
                    $this->mensagem->sucesso('Pacote de votos deletado com sucesso!')->flash();
                    Helpers::redirecionar('admin/ingressos/listar');
                } else {
                    $this->mensagem->erro($pctVotos->erro())->flash();
                    Helpers::redirecionar('admin/ingressos/listar');
                }
            }
        }
    }
}
