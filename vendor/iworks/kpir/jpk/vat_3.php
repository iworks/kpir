<?php
/*

Copyright 2017-2018 Marcin Pietrzak (marcin@iworks.pl)

this program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class iworks_kpir_jpk_vat_3 {

    public function __construct() {
    }

    public function show() {
    }

    private function template_header() {
        $data = '<?xml version="1.0" encoding="UTF-8"?>';
        $data .= '<JPK xmlns="http://jpk.mf.gov.pl/wzor/2016/10/26/10261/" xmlns:etd="http://crd.gov.pl/xml/schematy/dziedzinowe/mf/2016/01/25/eD/DefinicjeTypy/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
        return $data;
    }

    private function template_footer() {
        $data = '</JPK>';
        return $data;
    }

    /**
     * CelZlozenia - 1- złożenie pliku JPK_VAT
     *               2- korekta pliku JPK_VAT
     *               3... kolejny numer korekty
     * DataOd - Miesiąc, za który składany jest JPK_VAT
     * DataOd - Miesiąc, za który składany jest JPK_VAT
     */
    private function template_section_head() {
        $data = '<Naglowek>
            <KodFormularza wersjaSchemy="1-1" kodSystemowy="JPK_VAT (3)">JPK_VAT</KodFormularza>
            <WariantFormularza>3</WariantFormularza>
            <CelZlozenia>%CelZlozenia%</CelZlozenia>
            <DataWytworzeniaJPK>%DataWytworzeniaJPK%</DataWytworzeniaJPK>
            <DataOd>%DataOd%</DataOd>
            <DataDo>%DataDo%</DataDo>
            <NazwaSystemu>KPiR Base PLUGIN_VERSION</NazwaSystemu>
            </Naglowek>';
        return $data;
    }

    private function template_section_company() {
        $data = '
    <Podmiot1>
        <IdentyfikatorPodmiotu>
            <etd:NIP>%NIP%</etd:NIP>
            <etd:PelnaNazwa>%PelnaNazwa%</etd:PelnaNazwa>
        </IdentyfikatorPodmiotu>
    </Podmiot1>';
        return $data;
    }

    private function template_row_sale() {
        $data = '<SprzedazWiersz>
            <LpSprzedazy>1</LpSprzedazy>
            <NrKontrahenta>96121309455</NrKontrahenta>
            <NazwaKontrahenta>Mieczysława ZĄBKOWSKA</NazwaKontrahenta>
            <AdresKontrahenta>43-430 SKOCZÓW, ul. Szkolna 21 C</AdresKontrahenta>
            <DowodSprzedazy>R/20/18</DowodSprzedazy>
            <DataWystawienia>2018-01-04</DataWystawienia>
            <DataSprzedazy>2018-01-04</DataSprzedazy>
            </SprzedazWiersz>';
        return $data;
    }

    private function template_summary_sale() {
        $data = '<SprzedazCtrl>
<LiczbaWierszySprzedazy>2</LiczbaWierszySprzedazy>
<PodatekNalezny>12.36</PodatekNalezny>
</SprzedazCtrl>';
        return $data;
    }

    /**
     * K_43 – kwota netto nabycie środków trwałych
     * K_44 – kwota podatku naliczonego nabycie środków trwałych
     * K_45 – kwota netto nabycie towarów i usług pozostałych
     * K_46 – kwota podatku naliczonego nabycie towarów i usług pozostałych
     */
    private function template_row_purchase() {
        $data = '<SprzedazWiersz>
            <LpSprzedazy>1</LpSprzedazy>
            <NrKontrahenta>96121309455</NrKontrahenta>
            <NazwaKontrahenta>Mieczysława ZĄBKOWSKA</NazwaKontrahenta>
            <AdresKontrahenta>43-430 SKOCZÓW, ul. Szkolna 21 C</AdresKontrahenta>
            <DowodSprzedazy>R/20/18</DowodSprzedazy>
            <DataWystawienia>2018-01-04</DataWystawienia>
            <DataSprzedazy>2018-01-04</DataSprzedazy>
            <K_17>140.27</K_17>
            <K_18>11.22</K_18>
            </SprzedazWiersz>';
        return $data;
    }

}
