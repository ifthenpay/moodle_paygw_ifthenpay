<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Strings for component 'paygw_ifthenpay', language 'pt'
 *
 * @package    paygw_ifthenpay
 * @copyright  2025 ifthenpay <geral@ifthenpay.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Default.
$string['pluginname'] = 'ifthenpay';
$string['gatewayname'] = 'ifthenpay | Payment Gateway';
$string['gatewaydescription'] = '
Autorizada como prestadora de serviços de pagamento para processar pagamentos com
<strong>Credit Cards</strong>, <strong>Cofidis Pay</strong>, <strong>Apple Pay</strong>,
<strong>Google Pay</strong>, <strong>MB WAY</strong>, <strong>Bizum</strong>, <strong>Pix</strong>,
<strong>Multibanco</strong> and <strong>Payshop</strong>.
';


// Modal (moustache).
$string['modal:redirectingtoifthenpay'] = 'Rederecionando para ifthenpay | Payment Gateway';
$string['modal:pleasewait'] = 'Por favor aguarde...';


// Settings / headings.
$string['onboarding_title'] = 'Subscrição gratuita do serviço';
$string['api_heading'] = 'Ligação à ifthenpay';
$string['behavior_heading'] = 'Comportamento do pagamento';
$string['behavior_desc'] = 'Definições opcionais que afetam como esta gateway é apresentada aos utilizadores.';
$string['onboarding_html'] = '
  <ul>
    <li>Visite o site e <a href="https://ifthenpay.com/aderir/" target="_blank" rel="noopener">subscreva</a>.</li>
    <li>Faça download e preencha o contrato.</li>
    <li>Anexe os documentos solicitados.</li>
    <li>Solicite a criação da Gateway Key.</li>
    <li>Envie os documentos para <a href="mailto:ifthenpay@ifthenpay.com">ifthenpay@ifthenpay.com</a>.</li>
  </ul>
  <p><strong>Nota:</strong> Caso já tenha contrato com a ifthenpay, basta solicitar a criação da Gateway Key.</p>
  <p>Para mais informações visite <a href="https://ifthenpay.com" target="_blank" rel="noopener">ifthenpay.com</a>.</p>
';
$string['backoffice_key'] = 'Backoffice Key';
$string['backoffice_key_desc'] = 'Utilizada para autenticar chamadas à API e webhooks.';

// Validation / messages.
$string['error_invalidformat'] = 'Formato inválido. Use 1234-5678-9012-3456.';
$string['error_invalid_backoffice_key'] = 'A Backoffice Key não é válida. Verifique e tente novamente.';


// Errors for API responses.
$string['api:nobackofficekey_error'] = 'API: Nenhuma Backoffice Key configurada.';
$string['api:error_invalid_pbl_response'] = 'Resposta inválida da API Pay-by-Link.';
$string['api:error_invalid_json_get'] = 'Resposta JSON inválida no pedido GET: {$a}';
$string['api:error_invalid_json_post'] = 'Resposta JSON inválida no pedido POST.';
$string['api:error_http_request_failed'] = 'Falha na chamada HTTP: {$a}';
$string['api:error_http_status'] = 'Erro HTTP da API: {$a}';


// Form – sections & labels.
$string['form:gateway_configuration'] = 'Definições da gateway';
$string['form:gateway_key'] = 'Gateway Key';
$string['form:gateway_key_help'] = 'Precisa de outra key? <a href="mailto:suporte@ifthenpay.com">Contacte o suporte ifthenpay</a>. Novas keys e contas surgem automaticamente após ativação.';

$string['form:payment_configuration'] = 'Métodos de pagamento';
$string['form:payment_configuration_reqnote'] = '<strong>Obrigatório:</strong> Ative pelo menos um método de pagamento.';
$string['form:noaccounts'] = 'Sem contas disponíveis';

$string['form:other_configuration'] = 'Definições adicionais';
$string['form:default_method'] = 'Método predefinido (Opcional)';
$string['form:default_method_help'] =
    'Opcional. Se ativo, este método será o pré-selecionado no checkout quando multiplicos métodos estão ativos. Selecione "Nenhum" para que o cliente escolha sem a pré-seleção.';
$string['form:default_method_none'] = 'Nenhum';
$string['form:description'] = 'Descrição do checkout (Opcional)';
$string['form:description_help'] = 'Texto opcional, até 150 caracteres, apresentado no checkout.';

$string['form:missing_backoffice_key_inline'] = 'A Backoffice Key não está configurada. <a href="{$a}">Abrir definições</a>.';
$string['form:missing_gateway_keys_inline'] =
    'Não existe nenhuma Gateway Key configurada para o Moodle no seu backoffice da ifthenpay. Por favor, <a href="mailto:suporte@ifthenpay.com">contacte o suporte ifthenpay</a> para criar uma Gateway Key para o Moodle e atribuir os métodos de pagamento que pretende aceitar. Depois de criada, volte aqui e selecione-a.';

// Validation / messages.
$string['form:error_state_missing'] = 'Faltam dados de configuração. Por favor, tente guardar novamente.';
$string['form:error_no_methods_enabled'] = 'Ative pelo menos um método de pagamento.';
$string['form:error_default_not_enabled'] = 'O método predefinido "{$a}" tem de estar ativado nos métodos de pagamento.';
$string['form:error_default_unknown'] = 'O método predefinido selecionado "{$a}" não é reconhecido.';
$string['form:error_maxchars'] = 'Máximo de {$a} caracteres.';
$string['form:error_callback_activation'] = 'Falha ao ativar notificações de pagamento. Verifique a sua Backoffice Key e a conectividade à internet, depois guarde novamente. Erro: {$a}';


// Cancel/error page (processing flow).
$string['process:cancel_title']        = 'Pagamento não concluído';
$string['process:cancel_desc_cancel']  = 'O pagamento foi cancelado. Pode tentar novamente ou contactar o suporte.';
$string['process:cancel_desc_error']   = 'Ocorreu um erro ao processar o pagamento. Pode tentar novamente ou contactar o suporte.';
$string['process:status_canceled']     = 'Cancelado';
$string['process:status_error']        = 'Erro';
$string['process:btn_try_again']       = 'Tentar novamente';
$string['process:btn_contact_support'] = 'Contactar o suporte';
$string['process:not_found']           = 'Tentativa de pagamento não encontrada.';

// Processing / return page.
$string['process:return_title']       = 'A confirmar o seu pagamento';
$string['process:waiting']            = 'A verificar o estado…';
$string['process:waiting_hint']       = 'Esta verificação pode demorar alguns segundos. Pode tentar novamente uma vez ou regressar aos seus cursos.';
$string['process:order_reference']    = 'Referência da encomenda';
$string['process:transaction_id']     = 'ID da transação';
$string['process:amount']             = 'Montante';
$string['process:btn_retry']          = 'Tentar novamente';
$string['process:btn_go_to_courses']  = 'Ir para os meus cursos';
