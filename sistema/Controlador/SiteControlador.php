<?php

namespace sistema\Controlador;

use sistema\Nucleo\Controlador;
use sistema\Modelo\PostModelo;
use sistema\Nucleo\Helpers;
use sistema\Modelo\CategoriaModelo;
use sistema\Biblioteca\Paginar;
use sistema\Suporte\Email;
use sistema\Modelo\PedidoModelo;
use sistema\Biblioteca\Asaas;
use sistema\Modelo\ConfiguracaoModelo;
use sistema\Modelo\LandingPageModelo;
use sistema\Modelo\PacoteModelo;
use sistema\Nucleo\Conexao;
use sistema\Suporte\XDebug;

class SiteControlador extends Controlador
{

    public function __construct()
    {
        parent::__construct('templates/site/views');
    }


    /**
     * Landing Page (Capa do Site)
     */
    public function landing(): void
    {
        $landingModelo = new LandingPageModelo();
        $landing = $landingModelo->buscaPorId(1);

        if (!$landing) {
            $landing = (object) [
                'texto_topo' => 'CONCURSO OFICIAL',
                'titulo_principal' => 'Vote Pela Sua Miss',
                'subtitulo' => 'A beleza, a elegância e a simpatia estão em jogo.',
                'texto_botao' => 'VER CANDIDATAS',
                'url_botao' => 'votar',
                'imagem_fundo' => null,
                'status' => 1
            ];
        }

        $urlImagemFundo = $landing->imagem_fundo
            ? Helpers::url('uploads/imagens/thumbs/' . $landing->imagem_fundo)
            : null;

        echo $this->template->renderizar('landing.html', [
            'landing' => $landing,
            'urlImagemFundo' => $urlImagemFundo
        ]);
    }

    /**
     * Home Page
     */
    // public function index(?int $pagina = null): void
    // {
    //     $pagina = $pagina ?? 1;

    //     $postModelo = new PostModelo();
    //     $total = $postModelo->busca("status = :s", "s=1")->total();
    //     $paginar = new Paginar(Helpers::url('page'), $pagina, 24, 3, $total);
    //     $postsParaCards = $postModelo->busca("status = 1")
    //         ->ordem('titulo ASC')
    //         ->limite($paginar->limite())
    //         ->offset($paginar->offset())
    //         ->resultado(true);

    //     echo $this->template->renderizar('index.html', [
    //         'posts' => $postsParaCards,
    //         'paginacao' => $paginar->renderizar(),
    //         'paginacaoInfo' => $paginar->info(),
    //         'categorias' => $this->categorias(),
    //     ]);
    // }

    public function index(?int $pagina = null): void
    {
        $config = (new ConfiguracaoModelo())->buscaPorId(1);

        $limite = $config->posts_por_pagina ?? 24;
        $ordem = $config->ordenacao_posts ?? 'titulo ASC';

        $pagina = $pagina ?? 1;
        $postModelo = new PostModelo();

        $total = $postModelo->busca("status = :s", "s=1")->total();

        $paginar = new Paginar(Helpers::url('page'), $pagina, $limite, 3, $total);

        $postsParaCards = $postModelo->busca("status = 1")
            ->ordem($ordem)
            ->limite($paginar->limite())
            ->offset($paginar->offset())
            ->resultado(true);

        echo $this->template->renderizar('index.html', [
            'posts' => $postsParaCards,
            'paginacao' => $paginar->renderizar(),
            'paginacaoInfo' => $paginar->info(),
            'categorias' => $this->categorias(),
            'config' => $config
        ]);
    }

    /**
     * Busca posts 
     */
    public function buscar(): void
    {
        $busca = filter_input(INPUT_POST, 'busca', FILTER_DEFAULT);

        if (isset($busca)) {
            $termo = "%{$busca}%";
            $query = "status = 1 AND (titulo LIKE '{$termo}' OR categoria_id IN (SELECT id FROM categorias WHERE titulo LIKE '{$termo}'))";

            $posts = (new PostModelo())->busca($query)->limite(5)->resultado(true);

            if ($posts) {
                echo "<div class='list-group'>";
                foreach ($posts as $post) {
                    $imagemUrl = $post->capa ? Helpers::url('uploads/imagens/thumbs/' . $post->capa) : 'https://placehold.co/50';
                    $link = Helpers::url('post/') . $post->categoria()->slug . '/' . $post->slug;

                    echo "
                    <a href='{$link}' class='list-group-item list-group-item-action d-flex align-items-center gap-3 bg-dark text-light border-secondary'>
                        <div style='width: 40px; height: 40px; min-width: 40px;'>
                            <img src='{$imagemUrl}' alt='{$post->titulo}' class='rounded-circle' style='width: 100%; height: 100%; object-fit: cover;'>
                        </div>
                        <div class='flex-grow-1'>
                            <h6 class='mb-0 text-white' style='font-size: 14px;'>{$post->titulo}</h6>
                            <small class='text-primary' style='font-size: 11px;'>{$post->categoria()->titulo}</small>
                        </div>
                    </a>";
                }
                echo "</div>";
            }
        }
    }

    /**
     * Busca post por ID
     */
    public function post(string $categoria, string $slug): void
    {
        $post = (new PostModelo())->buscaPorSlug($slug);

        if (!$post) {
            Helpers::redirecionar('404');
        }

        $post->salvarVisitas();

        $pacotes = (new PacoteModelo())->busca('status = :s', 's=1')->resultado(true);

        echo $this->template->renderizar('post.html', [
            'post' => $post,
            'categorias' => $this->categorias(),
            'pacotes' => $pacotes
        ]);
    }

    /**
     * Categorias
     */
    public function categorias(): ?array
    {
        return (new CategoriaModelo())->busca("status = 1")->resultado(true);
    }

    /**
     * Lista posts por categoria
     */
    public function categoria(string $slug, ?int $pagina = null): void
    {
        $categoria = (new CategoriaModelo())->buscaPorSlug($slug);
        if (!$categoria) {
            Helpers::redirecionar('404');
        }
        $categoria->salvarVisitas();

        $posts = (new PostModelo());
        $total = $posts->busca('categoria_id = :c AND status = :s', "c={$categoria->id}&s=1 COUNT(id)", 'id')->total();

        $paginar = new Paginar(Helpers::url('categoria/' . $slug), ($pagina ?? 1), 10, 3, $total);

        echo $this->template->renderizar('categoria.html', [
            'posts' => $posts->busca("categoria_id = {$categoria->id} AND status = 1")->limite($paginar->limite())->offset($paginar->offset())->resultado(true),
            'paginacao' => $paginar->renderizar(),
            'paginacaoInfo' => $paginar->info(),
            'categorias' => $this->categorias(),
            'categoriaAtual' => $categoria
        ]);
    }

    /**
     * Sobre
     */
    public function sobre(): void
    {
        echo $this->template->renderizar('sobre.html', [
            'titulo' => 'Sobre nós',
            'categorias' => $this->categorias(),
        ]);
    }

    /**
     * ERRO 404
     */
    public function erro404(): void
    {
        echo $this->template->renderizar('404.html', [
            'titulo' => 'Página não encontrada',
            'categorias' => $this->categorias(),
        ]);
    }

    /**
     * Processa o pré-checkout e exibe a tela de pagamento
     */
    public function checkout(): void
    {
        $config = (new ConfiguracaoModelo())->buscaPorId(1);
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (!isset($dados['pacotes']) || !isset($dados['post_id'])) {
            Helpers::redirecionar();
            return;
        }

        $postBusca = (new PostModelo())->busca("id = :id", "id={$dados['post_id']}")->resultado(true);
        if (!$postBusca) {
            Helpers::redirecionar('404');
            return;
        }
        $post = $postBusca[0];
        $pacotesDb = (new PacoteModelo())->busca("status = 1")->resultado(true);

        $tabelaPrecos = [];
        if ($pacotesDb) {
            foreach ($pacotesDb as $pct) {
                $tabelaPrecos[$pct->quantidade] = [
                    'id'    => $pct->id,
                    'valor' => $pct->valor,
                    'taxa'  => $pct->taxa
                ];
            }
        }

        $subtotal = 0;
        $taxaUnicaAplicada = 0;
        $totalVotos = 0;
        $itensCarrinho = false;

        $pacoteIdParaSalvar = null;
        $tiposDiferentesSelecionados = 0;

        foreach ($dados['pacotes'] as $tipo => $quantidade) {
            $quantidade = intval($quantidade);

            if ($quantidade > 0 && isset($tabelaPrecos[$tipo])) {
                $precoUnitario = $tabelaPrecos[$tipo]['valor'];
                $taxaUnitaria  = $tabelaPrecos[$tipo]['taxa'];

                $pacoteIdParaSalvar = $tabelaPrecos[$tipo]['id'];
                $tiposDiferentesSelecionados++;

                $subtotal += $precoUnitario * $quantidade;

                if ($taxaUnitaria > $taxaUnicaAplicada) {
                    $taxaUnicaAplicada = $taxaUnitaria;
                }

                $totalVotos += $tipo * $quantidade;
                $itensCarrinho = true;
            }
        }

        if (!$itensCarrinho) {
            Helpers::redirecionar('post/' . $post->categoria()->slug . '/' . $post->slug);
            return;
        }

        if ($tiposDiferentesSelecionados > 1) {
            $pacoteIdParaSalvar = null;
        }

        $totalGeral = $subtotal + $taxaUnicaAplicada;

        echo $this->template->renderizar('checkout.html', [
            'titulo'        => 'Checkout - ' . $post->titulo,
            'post'          => $post,
            'totalVotos'    => $totalVotos,
            'subtotal'      => number_format($subtotal, 2, ',', '.'),
            'totalTaxas'    => number_format($taxaUnicaAplicada, 2, ',', '.'),
            'totalGeral'    => number_format($totalGeral, 2, ',', '.'),
            'subtotalFloat' => $subtotal,
            'taxaFloat'     => $taxaUnicaAplicada,
            'totalFloat'    => $totalGeral,
            'pacoteId'      => $pacoteIdParaSalvar,
            'config'        => $config
        ]);
    }

    /**
     * Processa o pagamento e gera o PIX
     */
    public function pagamentoProcessar(): void
    {
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (!$this->validarDadosPagamento($dados)) {

            $post = (new PostModelo())->buscaPorId($dados['post_id']);

            if (!$post) {
                Helpers::redirecionar();
                return;
            }

            echo $this->template->renderizar('checkout.html', [
                'titulo'        => 'Checkout - ' . $post->titulo,
                'post'          => $post,
                'totalVotos'    => $dados['total_votos'],
                'pacoteId'      => $dados['pacote_id'] ?? null,
                'subtotalFloat' => $dados['valor_subtotal'],
                'taxaFloat'     => $dados['valor_taxa'],
                'totalFloat'    => $dados['valor_total'],
                'subtotal'      => number_format((float)$dados['valor_subtotal'], 2, ',', '.'),
                'totalTaxas'    => number_format((float)$dados['valor_taxa'], 2, ',', '.'),
                'totalGeral'    => number_format((float)$dados['valor_total'], 2, ',', '.'),
                'form'          => $dados
            ]);

            return;
        }

        $pedido = new PedidoModelo();

        $pedido->post_id = $dados['post_id'];
        $pedido->pacote_id = !empty($dados['pacote_id']) ? $dados['pacote_id'] : null;

        $pedido->valor_subtotal = $dados['valor_subtotal'];
        $pedido->valor_taxa     = $dados['valor_taxa'];
        $pedido->valor_total    = $dados['valor_total'];

        $pedido->total_votos = $dados['total_votos'];

        $pedido->cliente_nome = $dados['nome'];
        $pedido->cliente_cpf = preg_replace('/[^0-9]/', '', $dados['cpf']);
        // $pedido->cliente_email = $dados['email'];
        $pedido->cliente_email = null;
        // $pedido->cliente_telefone = preg_replace('/[^0-9]/', '', $dados['telefone']);
        $pedido->cliente_telefone = null;
        // $pedido->status = 'AGUARDANDO';

        if (!$pedido->salvar()) {
            $this->mensagem->erro('Erro ao salvar pedido no banco. Tente novamente.')->flash();
            Helpers::redirecionar();
            return;
        }

        $asaas = new Asaas();

        $resultado = $asaas->gerarPixVenda(
            [
                'nome' => $pedido->cliente_nome,
                'cpf' => $pedido->cliente_cpf,
                'email' => $pedido->cliente_email,
                'telefone' => $pedido->cliente_telefone
            ],
            (float) $pedido->valor_total,
            "Votos para " . $dados['post_titulo'],
            $pedido->id
        );

        if ($resultado['erro']) {
            $pedido->status = 'ERRO';
            $pedido->salvar();

            $this->mensagem->erro('Erro no Asaas: ' . $resultado['mensagem'])->flash();
            $post = (new PostModelo())->buscaPorId($dados['post_id']);
            if ($post) {
                Helpers::redirecionar('post/' . $post->categoria()->slug . '/' . $post->slug);
            } else {
                Helpers::redirecionar();
            }

            return;
        }

        $pedido->asaas_id = $resultado['id_transacao'];
        $pedido->pix_qrcode = $resultado['payload'];
        $pedido->pix_img = $resultado['encodedImage'];
        $pedido->salvar();

        // Redireciona para a página de visualização (Evita problema do F5)
        Helpers::redirecionar('pagamento/' . $pedido->id);
    }

    /**
     * Exibe a tela de pagamento de um pedido existente (GET)
     */
    public function pagamento(int $idPedido): void
    {
        $pedido = (new PedidoModelo())->buscaPorId($idPedido);

        if (!$pedido) {
            $this->mensagem->alerta('Pedido não encontrado.')->flash();
            Helpers::redirecionar();
            return;
        }

        if (empty($pedido->pix_qrcode)) {
            $this->mensagem->erro('Este pedido não possui dados de pagamento válidos.')->flash();
            Helpers::redirecionar();
            return;
        }

        echo $this->template->renderizar('pagamento.html', [
            'titulo'       => 'Pagamento PIX',
            'pedido'       => $pedido,
            'copiaCola'    => $pedido->pix_qrcode,
            'imagemQrcode' => $pedido->pix_img
        ]);
    }

    public function pagamentoVerificar(): void
    {
        $idPedido = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if (!$idPedido) {
            Helpers::json('erro', 'ID inválido');
        }

        $pedidoModelo = new PedidoModelo();
        $pedido = $pedidoModelo->buscaPorId($idPedido);

        if (!$pedido) {
            Helpers::json('erro', 'Pedido não encontrado');
        }

        if ($pedido->status == 'PAGO') {
            Helpers::json('pago', 'Pagamento já confirmado');
        }

        $asaas = new Asaas();
        $cobranca = $asaas->consultarCobranca($pedido->asaas_id);

        if (isset($cobranca->status) && ($cobranca->status == 'RECEIVED' || $cobranca->status == 'CONFIRMED')) {

            $pdo = Conexao::getInstancia();
            $pdo->beginTransaction();

            try {
                $stmtPedido = $pdo->prepare("UPDATE pedidos SET status = 'PAGO', pago_em = NOW() WHERE id = :id");
                $stmtPedido->bindValue(':id', $pedido->id);
                $stmtPedido->execute();

                $post = new PostModelo();
                $post->id = $pedido->post_id;

                if (!$post->adicionarVotos($pedido->total_votos)) {
                    throw new \Exception("Erro ao somar votos");
                }

                if (!$post->adicionarReceita((float)$pedido->valor_total)) {
                    throw new \Exception("Erro ao somar receita");
                }

                $pdo->commit();
                Helpers::json('pago', 'Pagamento confirmado!');
            } catch (\Exception $e) {
                $pdo->rollBack();
                Helpers::json('erro', 'Erro: ' . $e->getMessage());
            }
        } else {
            Helpers::json('aguardando', 'Aguardando...');
        }
    }

    public function webhook(): void
    {
        $json = file_get_contents('php://input');
        $dados = json_decode($json, true);

        if (isset($dados['event']) && ($dados['event'] == 'PAYMENT_CONFIRMED' || $dados['event'] == 'PAYMENT_RECEIVED')) {
            $pagamento = $dados['payment'];
            $idTransacaoAsaas = $pagamento['id'];
            $pedidoModelo = new PedidoModelo();
            $pedidos = $pedidoModelo->busca("asaas_id = :id", "id={$idTransacaoAsaas}")->resultado(true);

            if ($pedidos) {
                $pedido = $pedidos[0];
                if ($pedido->status != 'PAGO') {
                    $pdo = Conexao::getInstancia();
                    $pdo->beginTransaction();

                    try {
                        $stmt = $pdo->prepare("UPDATE pedidos SET status = 'PAGO', pago_em = NOW() WHERE id = :id");
                        $stmt->bindValue(':id', $pedido->id);
                        $stmt->execute();
                        $post = new PostModelo();
                        $post->id = $pedido->post_id;

                        $post->adicionarVotos($pedido->total_votos);
                        $post->adicionarReceita((float)$pedido->valor_total);

                        $pdo->commit();
                        http_response_code(200);
                        echo json_encode(['status' => 'sucesso']);
                    } catch (\Exception $e) {
                        $pdo->rollBack();
                        http_response_code(500);
                    }
                } else {
                    http_response_code(200);
                }
            }
        } else {
            http_response_code(200);
        }
    }

    public function contato(): void
    {
        $dados = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);

        if (isset($dados)) {
            if (in_array('', $dados)) {
                Helpers::json('erro', 'Preencha todos os campos!');
            } elseif (!Helpers::validarEmail($dados['email'])) {
                Helpers::json('erro', 'E-mail inválido!');
            } else {
                try {
                    $email = new Email();
                    $view = $this->template->renderizar('emails/contato.html', [
                        'dados' => $dados,
                    ]);

                    $email->criar(
                        'Contato via Site - ' . SITE_NOME,
                        $view,
                        EMAIL_REMETENTE['email'],
                        EMAIL_REMETENTE['nome'],
                        $dados['email'],
                        $dados['nome']
                    );

                    $anexos = $_FILES['anexos'];
                    foreach ($anexos['tmp_name'] as $indice => $anexo) {
                        if (!$anexo == UPLOAD_ERR_OK) {
                            $email->anexar($anexo, $anexos['name'][$indice]);
                        }
                    }
                    $email->enviar(EMAIL_REMETENTE['email'], EMAIL_REMETENTE['nome']);

                    Helpers::json('successo', 'E-mail enviado com sucesso!');
                    Helpers::json('redirecionar', Helpers::url());
                } catch (\PHPMailer\PHPMailer\Exception $ex) {
                    Helpers::json('erro', 'Erro ao enviar e-mail. Tente novamente mais tarde! ' . $ex->getMessage());
                }
            }
        }

        echo $this->template->renderizar('contato.html', [
            'categorias' => $this->categorias(),
        ]);
    }

    /**
     * Valida os dados submetidos no formulário de pagamento
     * @param array|null $dados
     * @return bool
     */
    private function validarDadosPagamento(?array $dados): bool
    {
        if (!$dados || !isset($dados['post_id']) || !isset($dados['cpf'])) {
            $this->mensagem->alerta('Dados incompletos. Tente novamente.')->flash();
            return false;
        }

        if (empty($dados['nome'])) {
            $this->mensagem->erro('Por favor, preencha todos os campos obrigatórios.')->flash();
            return false;
        }

        $cpfLimpo = preg_replace('/[^0-9]/', '', $dados['cpf']);
        if (strlen($cpfLimpo) !== 11) {
            $this->mensagem->erro('O CPF informado é inválido.')->flash();
            return false;
        }

        if (!isset($dados['valor_total']) || (float)$dados['valor_total'] <= 0 || (int)$dados['total_votos'] <= 0) {
            $this->mensagem->erro('Erro nos valores do pedido. Tente novamente.')->flash();
            return false;
        }

        return true;
    }
}
