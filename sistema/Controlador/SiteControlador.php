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
use sistema\Modelo\PacoteModelo;

class SiteControlador extends Controlador
{

    public function __construct()
    {
        parent::__construct('templates/site/views');
    }

    /**
     * Home Page
     * @return void
     */
    public function index(): void
    {
        $postModelo = new PostModelo();

        $postsParaCards = $postModelo->busca("status = 1")
            ->ordem('id DESC')
            ->limite(25)
            ->resultado(true);

        echo $this->template->renderizar('index.html', [
            'posts' => $postsParaCards,
            'categorias' => $this->categorias(),
        ]);
    }

    /**
     * Busca posts 
     * @return void
     */
    public function buscar(): void
    {
        $busca = filter_input(INPUT_POST, 'busca', FILTER_DEFAULT);
        if (isset($busca)) {
            $posts = (new PostModelo())->busca("status = 1 AND titulo LIKE '%{$busca}%'")->limite(20)->resultado(true);
            if ($posts) {
                foreach ($posts as $post) {
                    echo "<li class='list-group-item fw-bold'><a href=" . Helpers::url('post/') . $post->categoria()->slug . '/' . $post->slug . ">$post->titulo</a></li>";
                }
            }
        }
    }

    /**
     * Busca post por ID
     * @param string $categoria apenas para o slug da categoria
     * @param string $slug
     * @return void
     */
    public function post(string $categoria, string $slug): void
    {
        $post = (new PostModelo())->buscaPorSlug($slug);

        if (!$post) {
            Helpers::redirecionar('404');
        }

        $post->salvarVisitas();
        $pacotes = (new \sistema\Modelo\PacoteModelo())->busca("status = 1")->ordem("ordem ASC")->resultado(true);
        echo $this->template->renderizar('post.html', [
            'post' => $post,
            'categorias' => $this->categorias(),
            'pacotes' => $pacotes
        ]);
    }

    /**
     * Categorias
     * @return array|null
     */
    public function categorias(): ?array
    {
        return (new CategoriaModelo())->busca("status = 1")->resultado(true);
    }

    /**
     * Lista posts por categoria
     * @param string $slug
     * @return void
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
        ]);
    }

    /**
     * Sobre
     * @return void
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
     * @return void
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
     * @return void
     */
    public function checkout(): void
    {
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
                    'valor' => $pct->valor,
                    'taxa'  => $pct->taxa
                ];
            }
        }

        $subtotal = 0;
        $totalTaxas = 0;
        $totalVotos = 0;
        $itensCarrinho = false;

        foreach ($dados['pacotes'] as $tipo => $quantidade) {
            $quantidade = intval($quantidade);

            if ($quantidade > 0 && isset($tabelaPrecos[$tipo])) {
                $precoUnitario = $tabelaPrecos[$tipo]['valor'];
                $taxaUnitaria  = $tabelaPrecos[$tipo]['taxa'];

                $subtotal   += $precoUnitario * $quantidade;
                $totalTaxas += $taxaUnitaria * $quantidade;
                $totalVotos += $tipo * $quantidade;

                $itensCarrinho = true;
            }
        }

        if (!$itensCarrinho) {
            Helpers::redirecionar('post/' . $post->categoria()->slug . '/' . $post->slug);
            return;
        }

        $totalGeral = $subtotal + $totalTaxas;

        echo $this->template->renderizar('checkout.html', [
            'titulo'     => 'Checkout - ' . $post->titulo,
            'post'       => $post,
            'totalVotos' => $totalVotos,
            'subtotal'   => number_format($subtotal, 2, ',', '.'),
            'totalTaxas' => number_format($totalTaxas, 2, ',', '.'),
            'totalGeral' => number_format($totalGeral, 2, ',', '.'),
            'totalFloat' => $totalGeral
        ]);
    }

    public function pagamentoProcessar(): void
    {
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (!isset($dados['post_id']) || !isset($dados['cpf'])) {
            $this->mensagem->alerta('Dados incompletos. Tente novamente.');
            Helpers::redirecionar();
            return;
        }

        $pedido = new PedidoModelo();

        $pedido->post_id = $dados['post_id'];
        $pedido->valor_total = $dados['valor_total'];
        $pedido->total_votos = $dados['total_votos'];

        $pedido->cliente_nome = $dados['nome'] . ' ' . $dados['sobrenome'];
        $pedido->cliente_cpf = preg_replace('/[^0-9]/', '', $dados['cpf']);
        $pedido->cliente_email = $dados['email'];
        $pedido->status = 'AGUARDANDO';

        if (!$pedido->salvar()) {
            $this->mensagem->erro('Erro ao salvar pedido. Tente novamente.');
            Helpers::redirecionar();
            return;
        }

        $asaas = new Asaas();

        $resultado = $asaas->gerarPixVenda(
            [
                'nome' => $pedido->cliente_nome,
                'cpf' => $pedido->cliente_cpf,
                'email' => $pedido->cliente_email
            ],
            (float) $pedido->valor_total,
            "Votos para " . $dados['post_titulo'],
            $pedido->id
        );

        if ($resultado['erro']) {
            $this->mensagem->erro('Erro no Asaas: ' . $resultado['mensagem']);
            Helpers::redirecionar();
            return;
        }

        $pedido->asaas_id = $resultado['id_transacao'];
        $pedido->pix_qrcode = $resultado['payload'];
        $pedido->pix_img = $resultado['encodedImage'];
        $pedido->salvar();

        echo $this->template->renderizar('pagamento.html', [
            'titulo' => 'Pagamento PIX',
            'pedido' => $pedido,
            'copiaCola' => $resultado['payload'],
            'imagemQrcode' => $resultado['encodedImage']
        ]);
    }

    /**
     * Contato
     * @return void
     */
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
}
