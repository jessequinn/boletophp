<?php

/**
 * Class BoletoPHP
 */
class BoletoPHP
{

    /** @var \Twig\Loader\FilesystemLoader $loader */
    private $loader;

    /** @var \Twig\Environment $twig */
    private $twig;

    /** @var DateTime|boolean $object */
    private $object;

    /**
     * Todas informacoes sobre o boleto.
     *
     * @var array
     */
    private $dadosboleto;

    /**
     * BoletoPHP constructor.
     *
     * @param array $dadosboleto
     * @param array $order
     * @param array $client_endereco
     * @param array $empresa_endereco
     * @throws Exception
     */
    public function __construct($dadosboleto, $order, $client_endereco, $empresa_endereco)
    {
        $this->loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
        $this->twig = new \Twig\Environment($this->loader, ['cache' => __DIR__ . '/compilation_cache', 'auto_reload' => true]);
        $function = new \Twig\TwigFunction('fbarcode', [$this, 'fbarcode']);
        $this->twig->addFunction($function);

        $this->object = DateTime::createFromFormat('Y-m-d', $order['data']);

        if (!is_object($this->object)):
            throw new Exception('DateTime::createFromFormat error');
        endif;

        $this->object->add(new DateInterval('P' . $dadosboleto['prazo'] . 'D'));
        $this->dadosboleto['inicio_nosso_numero'] = date('y');
        $this->dadosboleto['nosso_numero'] = str_pad($order['id'], 5, '0', STR_PAD_LEFT);
        $this->dadosboleto['numero_documento'] = str_pad($order['id'], 5, '0', STR_PAD_LEFT);
        $this->dadosboleto['data_vencimento'] = $this->object->format('d/m/Y');
        $this->dadosboleto['data_documento'] = date('d/m/Y');
        $this->dadosboleto['data_processamento'] = date('d/m/Y');
        $this->dadosboleto['valor_boleto'] = number_format(str_replace(',', '.', $order['valor_total']) + $dadosboleto['taxa'], 2, ',', '');
        $this->dadosboleto['sacado'] = $order['nome_completo'];
        $this->dadosboleto['endereco1'] = $client_endereco['endereco'] . ', ' . $client_endereco['numero'];
        $this->dadosboleto['endereco2'] = $client_endereco['cidade'] . ' - ' . $client_endereco['estado'] . ' -  CEP: ' . $client_endereco['cep'];
        $this->dadosboleto['demonstrativo1'] = $dadosboleto['demonstrativo1'];
        $this->dadosboleto['demonstrativo2'] = $dadosboleto['demonstrativo2'];
        $this->dadosboleto['demonstrativo3'] = $dadosboleto['demonstrativo3'];
        $this->dadosboleto['instrucoes1'] = $dadosboleto['instrucoes1'];
        $this->dadosboleto['instrucoes2'] = $dadosboleto['instrucoes2'];
        $this->dadosboleto['instrucoes3'] = $dadosboleto['instrucoes3'];
        $this->dadosboleto['instrucoes4'] = $dadosboleto['instrucoes4'];
        $this->dadosboleto['quantidade'] = $dadosboleto['quantidade'];
        $this->dadosboleto['valor_unitario'] = $dadosboleto['valor_unitario'];
        $this->dadosboleto['aceite'] = $dadosboleto['aceite'];
        $this->dadosboleto['especie'] = $dadosboleto['especie'];
        $this->dadosboleto['especie_doc'] = $dadosboleto['especie_doc'];
        $this->dadosboleto['agencia'] = $dadosboleto['agencia'];
        $this->dadosboleto['conta'] = $dadosboleto['conta'];
        $this->dadosboleto['conta_dv'] = $dadosboleto['conta_dv'];
        $this->dadosboleto['posto'] = $dadosboleto['posto'];
        $this->dadosboleto['byte_idt'] = $dadosboleto['byte_idt'];
        $this->dadosboleto['carteira'] = $dadosboleto['carteira'];
        $this->dadosboleto['identificacao'] = $dadosboleto['identificacao'];
        $this->dadosboleto['cpf_cnpj'] = $dadosboleto['cpf_cnpj'];
        $this->dadosboleto['endereco'] = $empresa_endereco['endereco_completo'];
        $this->dadosboleto['cidade_uf'] = $empresa_endereco['cidade'] . ' / ' . $empresa_endereco['estado'];
        $this->dadosboleto['cedente'] = $dadosboleto['cedente'];

        // TODO: sort for banco
        $codigo_banco_com_dv = $this->geraCodigoBanco($dadosboleto['codigo_banco']);
        $fatorVencimento = $this->fatorVencimento($this->dadosboleto["data_vencimento"]);
        $valor = $this->formataNumero($this->dadosboleto["valor_boleto"], 10, 0, "valor");
        $agencia = $this->formataNumero($this->dadosboleto["agencia"], 4, 0);
        $conta = $this->formataNumero($this->dadosboleto["conta"], 5, 0);
        $conta_dv = $this->formataNumero($this->dadosboleto["conta_dv"], 1, 0);
        $carteira = $this->dadosboleto["carteira"];
        $nnum = $this->formataNumero($this->dadosboleto["nosso_numero"], 8, 0);
        $codigo_barras = $dadosboleto['codigo_banco'] . $dadosboleto['nummoeda'] . $fatorVencimento . $valor . $carteira . $nnum . $this->module10($agencia . $conta . $carteira . $nnum) . $agencia . $conta . $this->module10($agencia . $conta) . '000';
        $dv = $this->digitoVerificadorBarra($codigo_barras);
        $linha = substr($codigo_barras, 0, 4) . $dv . substr($codigo_barras, 4, 43);
        $nossonumero = $carteira . '/' . $nnum . '-' . $this->module10($agencia . $conta . $carteira . $nnum);
        $agencia_codigo = $agencia . " / " . $conta . "-" . $this->module10($agencia . $conta);
        $this->dadosboleto["codigo_barras"] = $linha;
        $this->dadosboleto["linha_digitavel"] = $this->montaLinhaDigitavel($linha); // verificar
        $this->dadosboleto["agencia_codigo"] = $agencia_codigo;
        $this->dadosboleto["nosso_numero"] = $nossonumero;
        $this->dadosboleto["codigo_banco_com_dv"] = $codigo_banco_com_dv;
    }


    /**
     * Generate boleto in HTML format using twig.
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function generateBoleto($banco)
    {
        switch ($banco):
            case 'itau':
                $template = $this->twig->load('layout_itau.html.twig');
                return $template->render(['dadosboleto' => $this->dadosboleto]);
                break;
            case 'sicredi':
                $template = $this->twig->load('layout_sicredi.html.twig');
                return $template->render(['dadosboleto' => $this->dadosboleto]);
                break;
        endswitch;
    }

    /**
     * @param $numero
     * @return float|int|mixed|string
     */
    private function digitoVerificadorBarra($numero)
    {
        $resto2 = $this->module11($numero, 9, 1);
        $digito = 11 - $resto2;

        if ($digito == 0 || $digito == 1 || $digito == 10 || $digito == 11):
            $dv = 1;
        else:
            $dv = $digito;
        endif;

        return $dv;
    }

    /**
     * @param $numero
     * @param $loop
     * @param $insert
     * @param string $tipo
     * @return mixed|string
     */
    private function formataNumero($numero, $loop, $insert, $tipo = "geral")
    {
        if ($tipo == "geral"):
            $numero = str_replace(",", "", $numero);
            while (strlen($numero) < $loop):
                $numero = $insert . $numero;
            endwhile;
        endif;

        if ($tipo == "valor"):
            /*
            retira as virgulas
            formata o numero
            preenche com zeros
            */
            $numero = str_replace(",", "", $numero);
            while (strlen($numero) < $loop):
                $numero = $insert . $numero;
            endwhile;
        endif;

        if ($tipo == "convenio"):
            while (strlen($numero) < $loop):
                $numero = $numero . $insert;
            endwhile;
        endif;

        return $numero;
    }

    /**
     * @param $entra
     * @param $comp
     * @return bool|string
     */
    private function esquerda($entra, $comp)
    {
        return substr($entra, 0, $comp);
    }

    /**
     * @param $entra
     * @param $comp
     * @return bool|string
     */
    private function direita($entra, $comp)
    {
        return substr($entra, strlen($entra) - $comp, $comp);
    }

    /**
     * @param $data
     * @return float|int
     */
    private function fatorVencimento($data)
    {
        $data = explode("/", $data);
        $ano = $data[2];
        $mes = $data[1];
        $dia = $data[0];

        return (abs(($this->dateToDays("1997", "10", "07")) - ($this->dateToDays($ano, $mes, $dia))));
    }

    /**
     * @param $year
     * @param $month
     * @param $day
     * @return float|int
     */
    private function dateToDays($year, $month, $day)
    {
        $century = substr($year, 0, 2);
        $year = substr($year, 2, 2);

        if ($month > 2):
            $month -= 3;
        else:
            $month += 9;
            if ($year):
                $year--;
            else:
                $year = 99;
                $century--;
            endif;
        endif;

        return (floor((146097 * $century) / 4) + floor((1461 * $year) / 4) + floor((153 * $month + 2) / 5) + $day + 1721119);
    }

    /**
     * @param $num
     * @return int
     */
    function module10($num)
    {
        $numtotal10 = 0;
        $fator = 2;

        // Separacao dos numeros
        for ($i = strlen($num); $i > 0; $i--):
            // pega cada numero isoladamente
            $numeros[$i] = substr($num, $i - 1, 1);
            // Efetua multiplicacao do numero pelo (falor 10)
            // 2002-07-07 01:33:34 Macete para adequar ao Mod10 do Ita�
            $temp = $numeros[$i] * $fator;
            $temp0 = 0;

            foreach (preg_split('//', $temp, -1, PREG_SPLIT_NO_EMPTY) as $k => $v):
                $temp0 += $v;
            endforeach;

            $parcial10[$i] = $temp0; //$numeros[$i] * $fator;
            // monta sequencia para soma dos digitos no (modulo 10)
            $numtotal10 += $parcial10[$i];

            if ($fator == 2):
                $fator = 1;
            else:
                $fator = 2; // intercala fator de multiplicacao (modulo 10)
            endif;
        endfor;

        // v�rias linhas removidas, vide fun��o original
        // Calculo do modulo 10
        $resto = $numtotal10 % 10;
        $digito = 10 - $resto;
        if ($resto == 0):
            $digito = 0;
        endif;

        return $digito;
    }

    /**
     * @param $num
     * @param int $base
     * @param int $r
     * @return int
     */
    private function module11($num, $base = 9, $r = 0)
    {
        $soma = 0;
        $fator = 2;

        /* Separacao dos numeros */
        for ($i = strlen($num); $i > 0; $i--):
            // pega cada numero isoladamente
            $numeros[$i] = substr($num, $i - 1, 1);
            // Efetua multiplicacao do numero pelo falor
            $parcial[$i] = $numeros[$i] * $fator;
            // Soma dos digitos
            $soma += $parcial[$i];
            if ($fator == $base):
                // restaura fator de multiplicacao para 2
                $fator = 1;
            endif;
            $fator++;
        endfor;

        /* Calculo do modulo 11 */
        if ($r == 0):
            $soma *= 10;
            $digito = $soma % 11;

            if ($digito == 10):
                $digito = 0;
            endif;

            return $digito;
        elseif ($r == 1):
            $resto = $soma % 11;
            return $resto;
        endif;
    }

    /**
     * @param $codigo
     * @return string
     */
    private function montaLinhaDigitavel($codigo)
    {
        // campo 1
        $banco = substr($codigo, 0, 3);
        $moeda = substr($codigo, 3, 1);
        $ccc = substr($codigo, 19, 3);
        $ddnnum = substr($codigo, 22, 2);
        $dv1 = $this->module10($banco . $moeda . $ccc . $ddnnum);
        // campo 2
        $resnnum = substr($codigo, 24, 6);
        $dac1 = substr($codigo, 30, 1);
        $dddag = substr($codigo, 31, 3);
        $dv2 = $this->module10($resnnum . $dac1 . $dddag);
        // campo 3
        $resag = substr($codigo, 34, 1);
        $contadac = substr($codigo, 35, 6);
        $zeros = substr($codigo, 41, 3);
        $dv3 = $this->module10($resag . $contadac . $zeros);
        // campo 4
        $dv4 = substr($codigo, 4, 1);
        // campo 5
        $fator = substr($codigo, 5, 4);
        $valor = substr($codigo, 9, 10);
        $campo1 = substr($banco . $moeda . $ccc . $ddnnum . $dv1, 0, 5) . '.' . substr($banco . $moeda . $ccc . $ddnnum . $dv1, 5, 5);
        $campo2 = substr($resnnum . $dac1 . $dddag . $dv2, 0, 5) . '.' . substr($resnnum . $dac1 . $dddag . $dv2, 5, 6);
        $campo3 = substr($resag . $contadac . $zeros . $dv3, 0, 5) . '.' . substr($resag . $contadac . $zeros . $dv3, 5, 6);
        $campo4 = $dv4;
        $campo5 = $fator . $valor;

        return "$campo1 $campo2 $campo3 $campo4 $campo5";
    }

    /**
     * @param $numero
     * @return string
     */
    private function geraCodigoBanco($numero)
    {
        $parte1 = substr($numero, 0, 3);
        $parte2 = $this->module11($parte1);

        return $parte1 . "-" . $parte2;
    }

    /**
     * @param $valor
     */
    function fbarcode($valor)
    {

        $fino = 1;
        $largo = 3;
        $altura = 50;
        $barcodes[0] = "00110";
        $barcodes[1] = "10001";
        $barcodes[2] = "01001";
        $barcodes[3] = "11000";
        $barcodes[4] = "00101";
        $barcodes[5] = "10100";
        $barcodes[6] = "01100";
        $barcodes[7] = "00011";
        $barcodes[8] = "10010";
        $barcodes[9] = "01010";

        for ($f1 = 9; $f1 >= 0; $f1--):
            for ($f2 = 9; $f2 >= 0; $f2--):
                $f = ($f1 * 10) + $f2;
                $texto = "";

                for ($i = 1; $i < 6; $i++):
                    $texto .= substr($barcodes[$f1], ($i - 1), 1) . substr($barcodes[$f2], ($i - 1), 1);
                endfor;

                $barcodes[$f] = $texto;
            endfor;
        endfor;

        echo "<img src=imagens/p.png width=$fino height=$altura border=0><img src=imagens/b.png width=$fino height=$altura border=0><img src=imagens/p.png width=$fino height=$altura border=0><img src=imagens/b.png width=$fino height=$altura border=0><img";

        $texto = $valor;

        if ((strlen($texto) % 2) <> 0):
            $texto = "0" . $texto;
        endif;

        while (strlen($texto) > 0):
            $i = round($this->esquerda($texto, 2));
            $texto = $this->direita($texto, strlen($texto) - 2);
            $f = $barcodes[$i];

            for ($i = 1; $i < 11; $i += 2):
                if (substr($f, ($i - 1), 1) == "0"):
                    $f1 = $fino;
                else:
                    $f1 = $largo;
                endif;

                echo " src=imagens/p.png width=$f1 height=$altura border=0><img";

                if (substr($f, $i, 1) == "0"):
                    $f2 = $fino;
                else:
                    $f2 = $largo;
                endif;

                echo " src=imagens/b.png width=$f2 height=$altura border=0><img";
            endfor;
        endwhile;

        echo " src=imagens/p.png width=$largo height=$altura border=0><img src=imagens/b.png width=$fino height=$altura border=0><img src=imagens/p.png width=1 height=$altura border=0>";
    }

    /**
     * @return bool|DateTime
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param bool|DateTime $object
     */
    public function setObject($object)
    {
        $this->object = $object;
    }

    /**
     * @return array
     */
    public function getDadosboleto(): array
    {
        return $this->dadosboleto;
    }

    /**
     * @param array $dadosboleto
     */
    public function setDadosboleto(array $dadosboleto)
    {
        $this->dadosboleto = $dadosboleto;
    }
}