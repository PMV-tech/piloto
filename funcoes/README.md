functions.html - Documentação de Funções JavaScript
Página de documentação das funções utilitárias globais disponíveis no sistema BarberFlow.

📋 Visão Geral
Documentação completa das funções JavaScript disponíveis através do objeto window.BarberFlow.

📚 Funções Disponíveis
1. formatDate(date, format)
Descrição: Formata datas para exibição amigável
Parâmetros:

date: Date, string ou timestamp

format: Formato (padrão: 'dd/MM/yyyy')
Exemplo: BarberFlow.formatDate(new Date(), 'dd/MM/yyyy HH:mm')

2. formatCurrency(value)
Descrição: Formata valores como moeda brasileira (R$)
Parâmetros:

value: Número ou string numérica
Exemplo: BarberFlow.formatCurrency(150.75) → "R$ 150,75"

3. formatPhone(value)
Descrição: Formata números de telefone brasileiros
Parâmetros:

value: Número de telefone
Exemplo: BarberFlow.formatPhone('11999998888') → "(11) 99999-8888"

4. isValidEmail(email)
Descrição: Valida endereços de e-mail
Parâmetros:

email: String do e-mail
Retorna: true ou false
Exemplo: BarberFlow.isValidEmail('usuario@email.com')

5. showMessage(message, type, duration)
Descrição: Exibe mensagem toast na tela
Parâmetros:

message: Texto da mensagem

type: 'success', 'error', 'warning', 'info' (padrão: 'info')

duration: Duração em ms (padrão: 5000)
Exemplo: BarberFlow.showMessage('Sucesso!', 'success')

6. confirmAction(message, confirmText, cancelText)
Descrição: Modal de confirmação personalizado
Parâmetros:

message: Mensagem de confirmação

confirmText: Texto do botão confirmar

cancelText: Texto do botão cancelar
Retorna: Promise que resolve com true ou false
Exemplo: const confirmed = await BarberFlow.confirmAction('Excluir?')

7. showLoading() / hideLoading()
Descrição: Controle de overlay de loading
Uso: BarberFlow.showLoading() → BarberFlow.hideLoading()

8. exportToCsv(data, filename)
Descrição: Exporta array de objetos para CSV
Parâmetros:

data: Array de objetos

filename: Nome do arquivo (padrão: 'export.csv')
Exemplo: BarberFlow.exportToCsv(clientes, 'clientes.csv')

🎯 Interface Visual
Cards individuais para cada função

Ícone representativo por função

Parâmetros destacados com cores

Exemplos de código com syntax highlighting

Design responsivo e limpo

🔧 Tecnologias Utilizadas
HTML5, CSS3, JavaScript

Font Awesome para ícones

CSS Grid para layout

Estilos personalizados para código

📱 Layout
Cabeçalho com logo e descrição

Cards de função organizados verticalmente

Seções coloridas para parâmetros e exemplos

Botões de navegação no final

💡 Objetivo
Fornecer referência rápida para desenvolvedores utilizarem as funções utilitárias do sistema, melhorando produtividade e consistência no código.

Nota: Estas são funções do lado do cliente (frontend). Validações e operações críticas devem ser implementadas no backend.
