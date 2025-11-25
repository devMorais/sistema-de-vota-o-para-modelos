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
use sistema\Nucleo\Conexao;

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
    public function index(?int $pagina = null): void
    {
        $pagina = $pagina ?? 1;

        $postModelo = new PostModelo();
        $total = $postModelo->busca("status = :s", "s=1")->total();
        $paginar = new Paginar(Helpers::url('page'), $pagina, 24, 3, $total);
        $postsParaCards = $postModelo->busca("status = 1")
            ->ordem('id DESC')
            ->limite($paginar->limite())
            ->offset($paginar->offset())
            ->resultado(true);

        echo $this->template->renderizar('index.html', [
            'posts' => $postsParaCards,
            'paginacao' => $paginar->renderizar(),
            'paginacaoInfo' => $paginar->info(),
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
            // A Lógica: Busca posts ativos (status=1) ONDE:
            // O título parece com a busca OU o ID da categoria está na lista de categorias com esse nome
            $termo = "%{$busca}%";
            $query = "status = 1 AND (titulo LIKE '{$termo}' OR categoria_id IN (SELECT id FROM categorias WHERE titulo LIKE '{$termo}'))";

            $posts = (new PostModelo())->busca($query)->limite(5)->resultado(true);

            if ($posts) {
                echo "<div class='list-group'>";
                foreach ($posts as $post) {
                    $imagemUrl = $post->capa ? Helpers::url('uploads/imagens/thumbs/' . $post->capa) : 'https://placehold.co/50';
                    $link = Helpers::url('post/') . $post->categoria()->slug . '/' . $post->slug;

                    // Adicionei a Categoria no visual da busca para ajudar o usuário
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

        $pacotes = (new PacoteModelo())->busca('status = :s', 's=1')->resultado(true);

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
            'categoriaAtual' => $categoria
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
