<?php

namespace NFePHP\CTe3\Factories;

use DateTime;
use NFePHP\Common\Keys;
use NFePHP\Common\Signer;
use NFePHP\Common\Strings;

class ContingencyCTe
{
    /**
     * Corrects CTe fields when in contingency mode
     * @param string $xml CTe xml content
     * @param Contingency $contingency
     * @return string
     */
    public static function adjust($xml, Contingency $contingency)
    {
        if ($contingency->type == '') {
            return $xml;
        }
        $xml = Signer::removeSignature($xml);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $dom->loadXML($xml);

        $ide = $dom->getElementsByTagName('ide')->item(0);
        $cUF = $ide->getElementsByTagName('cUF')->item(0)->nodeValue;
        $cNF = $ide->getElementsByTagName('cCT')->item(0)->nodeValue;
        $nNF = $ide->getElementsByTagName('nCT')->item(0)->nodeValue;
        $serie = $ide->getElementsByTagName('serie')->item(0)->nodeValue;
        $mod = $ide->getElementsByTagName('mod')->item(0)->nodeValue;
        $dtEmi = new DateTime($ide->getElementsByTagName('dhEmi')->item(0)->nodeValue);
        $ano = $dtEmi->format('y');
        $mes = $dtEmi->format('m');
        $tpEmis = (string)$contingency->tpEmis;
        $emit = $dom->getElementsByTagName('emit')->item(0);
        $cnpj = $emit->getElementsByTagName('CNPJ')->item(0)->nodeValue;

        $motivo = trim(Strings::replaceUnacceptableCharacters($contingency->motive));
        $dt = new DateTime();
        $dt->setTimestamp($contingency->timestamp);
        $ide->getElementsByTagName('tpEmis')
            ->item(0)
            ->nodeValue = $contingency->tpEmis;
        if ($tpEmis == 5) {
            if (!empty($ide->getElementsByTagName('dhCont')->item(0)->nodeValue)) {
                $ide->getElementsByTagName('dhCont')
                    ->item(0)
                    ->nodeValue = $dt->format('Y-m-d\TH:i:sP');
            } else {
                $dhCont = $dom->createElement('dhCont', $dt->format('Y-m-d\TH:i:sP'));
                $ide->appendChild($dhCont);
            }
            if (!empty($ide->getElementsByTagName('xJust')->item(0)->nodeValue)) {
                $ide->getElementsByTagName('xJust')->item(0)->nodeValue = $motivo;
            } else {
                $xJust = $dom->createElement('xJust', $motivo);
                $ide->appendChild($xJust);
            }
        }
        //corrigir a chave
        $infCte = $dom->getElementsByTagName('infCte')->item(0);
        $chave = Keys::build(
            $cUF,
            $ano,
            $mes,
            $cnpj,
            $mod,
            $serie,
            $nNF,
            $tpEmis,
            $cNF
        );
        $ide->getElementsByTagName('cDV')->item(0)->nodeValue = substr($chave, -1);
        $infCte->setAttribute('Id', 'CTe' . $chave);
        return Strings::clearXmlString($dom->saveXML(), true);
    }
}
