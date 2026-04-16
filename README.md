# Plugin BigSheetImporter

Plugin para importação de uma planilha específica da Secult CE.

## Alterações no Schema
### Novas tabelas
```mermaid
erDiagram
s ||..o{ usr : belongs
s ||..o{ o : has
s ||..o{ r : has
registration ||..|| r : related

s[sheet_import]{
    integer       id                      PK
    timestamp     date
    integer       user_id                 FK
    integer       rows_amount
    integer       rows_saved
}
o[occurrence_import]{
    integer       id                      PK
    timestamp     date
    integer       sheet_id                FK
    char(1)       column_index
    integer       row_index
    varchar(255)  occurrence
    varchar(255)  given_value
}
r[row_sheet_import]{
    integer       id                      PK
    integer       sheet_id                FK
    varchar(13)   registration_number     UK
    char(20)      process_number          UK
    uinteger      sacc_number
    uinteger      term_number
    char(12)      interest_number
    money         trasfer_value
    timestamp     process_date
    timestamp     communication_to_proponent_sent_date
    timestamp     asjur_receipt_date
    timestamp     proponent_signature_terms_sent_date
    timestamp     casa_civil_sent_date
    timestamp     doe_publish_date
    timestamp     installment_request_date
    timestamp     eparcerias_conference_date
    timestamp     interest_date
    timestamp     payment_date
    timestamp     signed_term_validity_init_date
    timestamp     signed_term_validity_end_date
    text          fiscal_name
    char(14)      fiscal_cpf
    varchar(12)   fiscal_registry
    varchar(100)  instrument
    varchar(100)  municipality
}
```

# API - DOC. DO ENDPOINT

### Sessão que documentação o endpoint existente e a finalidade

ℹ️ - Informação da responsabilidade das notificações
#### GET  infoForNotificationsAccountability
**Rota**: bigsheet/infoForNotificationsAccountability
**Retorno**: Json
**Parâmetros**: Request com HTTP_ACCESS_TOKEN

ℹ️ - Atualiza o status da notificação para não ser notificado novamente
#### POST  updateNotificationStatus
**Rota**: bigsheet/updateNotificationStatus
**Retorno**: Json
**Parâmetros**: Request com HTTP_ACCESS_TOKEN , registration_number

---

ℹ️ - Retorna inscrições que possuem diligência ativa, 
cruzando dados do módulo Diligence com o RowSheet importado
#### GET  registrationsInDiligence
**Rota**: `bigsheet/registrationsInDiligence`
**Autenticação**: JWT no header `Authorization` + header `MapasSDK-REQUEST: true`

**Parâmetros de paginação** (opcionais):

| Parâmetro | Padrão | Descrição |
|-----------|--------|-----------|
| `@limit`  | `25`   | Quantidade de itens por página |
| `@page`   | `1`    | Número da página |
| `@offset` | calculado a partir de `@page` | Deslocamento direto (sobrescreve `@page`) |

**Exemplo de requisição**:
```
GET bigsheet/registrationsInDiligence?@limit=10&@page=1
Authorization: <jwt_token>
MapasSDK-REQUEST: true
```

**Retorno**:
```json
{
  "data": [
    {
      "registration_number": "on-123",
      "diligence_situation": 3,
      "row_sheet": {
        "municipality": "Fortaleza",
        "instrument": "Termo de Fomento",
        "sacc": 456
      },
      "agent": {
        "name": "Nome do Agente",
        "cpf": "000.000.000-00"
      }
    }
  ],
  "meta": {
    "total": 47,
    "page": 1,
    "limit": 10,
    "numPages": 5
  }
}
```

**Situações de diligência retornadas**:

| Valor | Descrição |
|-------|-----------|
| `2`   | Aberta |
| `3`   | Enviada ao proponente |
| `4`   | Respondida |
| `10`  | TADO gerado |

---

ℹ️ - Retorna oportunidades com diligência ativa (metadado `use_diligence = Sim`), vinculadas a projetos com o selo de id 16, com suas respectivas inscrições e dados da planilha importada
#### GET  opportunitiesWithDiligence
**Rota**: `pc/lista`
**Autenticação**: JWT no header `Authorization` + header `MapasSDK-REQUEST: true`

**Parâmetros de paginação** (opcionais):

| Parâmetro | Padrão | Descrição |
|-----------|--------|-----------|
| `@limit`  | `25`   | Quantidade de itens por página |
| `@page`   | `1`    | Número da página |
| `@offset` | calculado a partir de `@page` | Deslocamento direto (sobrescreve `@page`) |

**Exemplo de requisição**:
```
GET pc/lista?@limit=10&@page=1
Authorization: <jwt_token>
MapasSDK-REQUEST: true
```

**Retorno**:
```json
{
  "data": [
    {
      "id": 123,
      "oportunidade_pai": "Nome do edital pai",
      "nome": "Nome da fase de inscrição",
      "projeto": "Nome do projeto",
      "nome_agent": "Nome do agente responsável pela oportunidade",
      "inscricao_id": 456,
      "inscricao_numero": "CE2025001",
      "inscricao_status": 10,
      "inscricao_agente": "Nome do agente da inscrição",
      "inscricao_agente_cpf": "000.000.000-00",
      "sacc_number": 789,
      "instrument": "Termo de Fomento",
      "municipality": "Fortaleza"
    }
  ],
  "meta": {
    "total": 47,
    "page": 1,
    "limit": 10,
    "numPages": 5
  }
}
```

> Cada linha representa uma inscrição. Uma mesma oportunidade pode aparecer em múltiplas linhas caso possua mais de uma inscrição. Campos `sacc_number`, `instrument` e `municipality` são `null` quando não há registro na planilha importada para aquela inscrição.

**Status de inscrição (`inscricao_status`)**:

| Valor | Descrição |
|-------|-----------|
| `1`   | Rascunho |
| `2`   | Enviada |
| `3`   | Inválida |
| `4`   | Não selecionada |
| `5`   | Suplente |
| `10`  | Aprovada |
