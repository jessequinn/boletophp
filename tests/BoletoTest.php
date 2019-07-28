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
            'demonstrativo1' => 'Pagamento de Compra na .COM',
            'demonstrativo2' => 'Taxa bancária - R$ ' . number_format(0, 2, ',', ''), // based on taxa = 0
            'demonstrativo3' => 'ATENÇÃO: SE SEU PEDIDO FOI FEITO NO PONTO DE APOIO NÃO PAGUE NO BANCO, PAGUE DIRETAMENTE NO PONTO DE APOIO',            'instrucoes1' => '- Não receber após o vencimento',
            'instrucoes2' => '',
            'instrucoes3' => '',
            'instrucoes4' => '',
            'quantidade' => '',
            'valor_unitario' => '',
            'aceite' => '',
            'especie' => 'R$',
            'especie_doc' => '',
            'codigo_banco' => '341', // itau
            'nummoeda' => '9', // itau TODO: what does this number mean?
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
        $client_endereco = [
            'endereco' => 'Rua joao pessoa',
            'numero' => 2832,
            'cidade' => 'Blumenau',
            'estado' => 'Santa Catarina',
            'bairro' => 'Velha',
            'complemento' => '',
            'cep' => '89035-256',
        ];

        /** @var array $empresa_endereco */
        $empresa_endereco = [
            'endereco_completo' => 'full address here',
            'cidade' => 'Toronto',
            'estado' => 'Ontario',
            'nome' => '.COM',
        ];

        $boletoPHP = new BoletoPHP($dadosboleto, $order, $client_endereco, $empresa_endereco);

//        file_put_contents('test.html', $boletoPHP->generateBoleto('itau'));

        $this->assertEquals('19', $boletoPHP->getDadosboleto()['inicio_nosso_numero']);
    }
}