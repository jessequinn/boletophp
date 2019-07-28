<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class BoletoTest extends TestCase
{
    public function testItauBoletoCreation(): void
    {
        /** @var array $dadosboleto */
        $dadosboleto = [
            'prazo' => 3,
            'taxa' => 0,
            'taxa_bancaria' => '',
            'demonstrativo3' => 'ATENÇÃO: SE SEU PEDIDO FOI FEITO NO PONTO DE APOIO NÃO PAGUE NO BANCO, PAGUE DIRETAMENTE NO PONTO DE APOIO',
            'instrucoes1' => '- Não receber após o vencimento',
            'instrucoes2' => '',
            'instrucoes3' => '',
            'instrucoes4' => '',
            'quantidade' => '',
            'valor_unitario' => '',
            'aceite' => '',
            'especie' => 'R$',
            'especie_doc' => '',
            'agencia' => '1565',
            'conta' => '13877',
            'conta_dv' => '4',
            'posto' => '03',
            'byte_idt' => '2',
            'carteira' => '03',
            'identificacao' => 'BoletoPhp - Código Aberto de Sistema de Boletos',
            'cpf_cnpj' => '',
            'cedente' => 'Rede Facil Brasil Ltda - ME',
        ];

        /** @var array $order */
        $order = [
            'id' => 123,
            'data' => date('Y-m-d'),
            'valor_total' => 24.90,
            'nome_completo' => 'Jesse Quinn',
            'empresa_nome' => '.COM',
        ];

        /** @var array $endereco */
        $endereco = [
            'endereco' => 'Rua joao pessoa',
            'numero' => 2832,
            'cidade' => 'Blumenau',
            'estado' => 'Santa Catarina',
            'bairro' => 'Velha',
            'complemento' => '',
            'cep' => '89035-256',
        ];

        $boletoPHP = new BoletoPHP($dadosboleto, $order, $endereco);

//        var_dump($boletoPHP->getDadosboleto());

        echo $boletoPHP->getDadosboleto()['codigo_barras'];

        file_put_contents('test.html', $boletoPHP->generateBoleto());

        $this->assertEquals('19', $boletoPHP->getDadosboleto()['inicio_nosso_numero']);
    }
}