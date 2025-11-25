<?php

namespace sistema\Modelo;

use sistema\Nucleo\Conexao;
use sistema\Nucleo\Modelo;

/**
 * Classe PostModelo
 *
 * @author Fernando Aguiar
 */
class PostModelo extends Modelo
{

    public function __construct()
    {
        parent::__construct('posts');
    }

    /**
     * Busca a categoria pelo ID
     * @return CategoriaModelo|null
     */
    public function categoria(): ?CategoriaModelo
    {
        if ($this->categoria_id) {
            return (new CategoriaModelo())->buscaPorId($this->categoria_id);
        }
        return null;
    }

    /**
     * Busca o usuário pelo ID
     * @return UsuarioModelo|null
     */
    public function usuario(): ?UsuarioModelo
    {
        if ($this->usuario_id) {
            return (new UsuarioModelo())->buscaPorId($this->usuario_id);
        }
        return null;
    }

    /**
     * Salva o post com slug
     * @return bool
     */
    public function salvar(): bool
    {
        $this->slug();
        return parent::salvar();
    }

    /**
     * Adiciona votos de forma segura (Atômica) para evitar perda em acessos simultâneos
     * @param int $quantidade
     * @return bool
     */
    public function adicionarVotos(int $quantidade): bool
    {
        if (empty($this->id)) {
            return false;
        }

        $query = "UPDATE {$this->tabela} SET votos = votos + :qtd WHERE id = :id";

        try {
            $stmt = Conexao::getInstancia()->prepare($query);
            $stmt->bindValue(':qtd', $quantidade, \PDO::PARAM_INT);
            $stmt->bindValue(':id', $this->id, \PDO::PARAM_INT);
            $stmt->execute();

            return true;
        } catch (\PDOException $e) {
            $this->erro = $e->getMessage();
            return false;
        }
    }

    /**
     * Adiciona receita financeira ao post de forma segura
     * @param float $valor
     * @return bool
     */
    public function adicionarReceita(float $valor): bool
    {
        if (empty($this->id)) {
            return false;
        }

        $query = "UPDATE {$this->tabela} SET receita = receita + :valor WHERE id = :id";

        try {
            $stmt = Conexao::getInstancia()->prepare($query);
            $stmt->bindValue(':valor', $valor);
            $stmt->bindValue(':id', $this->id, \PDO::PARAM_INT);
            $stmt->execute();

            return true;
        } catch (\PDOException $e) {
            $this->erro = $e->getMessage();
            return false;
        }
    }
}
