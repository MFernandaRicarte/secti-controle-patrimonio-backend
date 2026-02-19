set -euo pipefail

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3307}"
DB_USER="${DB_USER:-secti_user}"
DB_PASS="${DB_PASS:-secti123}"
DB_NAME="${DB_NAME:-secti}"

MYSQL=(mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME")

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
MIG_DIR="$ROOT_DIR/db/migracoes"

RESET_DB="false"
if [[ "${1:-}" == "--reset" ]]; then
  RESET_DB="true"
fi

run_sql_file() {
  local f="$1"
  if [[ ! -f "$f" ]]; then
    echo "!! Arquivo não encontrado: $f"
    exit 1
  fi

  echo "==> Rodando $(basename "$f")"

  local out
  if ! out=$("${MYSQL[@]}" < "$f" 2>&1); then
    if echo "$out" | grep -Eq "Duplicate column name|Duplicate key name|already exists|Can't create table|Failed to open the referenced table"; then
      echo "==> Aviso (ignorando): $(basename "$f")"
      echo "$out" | tail -n 3
      return 0
    fi

    echo "!! ERRO em $(basename "$f")"
    echo "$out"
    exit 1
  fi
}

echo "==> Usando DB: $DB_USER@$DB_HOST:$DB_PORT/$DB_NAME"
echo "==> Pasta migracoes: $MIG_DIR"

if [[ "$RESET_DB" == "true" ]]; then
  echo "==> RESET: dropando e recriando banco $DB_NAME"
  mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" -e "DROP DATABASE IF EXISTS \`$DB_NAME\`; CREATE DATABASE \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
fi

run_sql_file "$MIG_DIR/20251105_0001_TABELAS_INICIAIS.sql"
run_sql_file "$MIG_DIR/20251105_0002_SETOR_SALA.sql"
run_sql_file "$MIG_DIR/20251105_0003_FORNECEDOR_CATEGORIA.sql"
run_sql_file "$MIG_DIR/20251105_0004_ITENS_E_ENTRADAS_ESTOQUE.sql"
run_sql_file "$MIG_DIR/20251105_0005_BENS_E_ENTRADAS_BEM.sql"

if [[ -f "$MIG_DIR/20260108_0014_LICITACOES.sql" ]]; then
  run_sql_file "$MIG_DIR/20260108_0014_LICITACOES.sql"
fi

if [[ -f "$MIG_DIR/20250116_0016_LICITACOES_DOC_RECRIAR.sql" ]]; then
  run_sql_file "$MIG_DIR/20250116_0016_LICITACOES_DOC_RECRIAR.sql"
fi
if [[ -f "$MIG_DIR/20250119_0021_LICITACOES_CRIADO_E_ATUALIZADO.sql" ]]; then
  run_sql_file "$MIG_DIR/20250119_0021_LICITACOES_CRIADO_E_ATUALIZADO.sql"
fi
if [[ -f "$MIG_DIR/20250120_0022_ADD_PRIORIDADE_ALERTAS.sql" ]]; then
  run_sql_file "$MIG_DIR/20250120_0022_ADD_PRIORIDADE_ALERTAS.sql"
fi

SKIP_FILES=(
  "20251109_0008_USUARIOS_PERFIL_UNICO.sql"
  "20251127_0008_AJUSTES_BENS_LISTAGEM.sql"
  "20251218_0011_ALTER_ENTRADAS_ESTOQUE_USUARIO.sql"
  "20260129_0021_FASES_TRAMITACOES.sql"
)

SKIP_FILES+=(
  "20251105_0001_TABELAS_INICIAIS.sql"
  "20251105_0002_SETOR_SALA.sql"
  "20251105_0003_FORNECEDOR_CATEGORIA.sql"
  "20251105_0004_ITENS_E_ENTRADAS_ESTOQUE.sql"
  "20251105_0005_BENS_E_ENTRADAS_BEM.sql"
  "20260108_0014_LICITACOES.sql"
  "20250116_0016_LICITACOES_DOC_RECRIAR.sql"
  "20250119_0021_LICITACOES_CRIADO_E_ATUALIZADO.sql"
  "20250120_0022_ADD_PRIORIDADE_ALERTAS.sql"
)

should_skip() {
  local base
  base="$(basename "$1")"
  for s in "${SKIP_FILES[@]}"; do
    if [[ "$base" == "$s" ]]; then
      return 0
    fi
  done
  return 1
}

while IFS= read -r f; do
  if should_skip "$f"; then
    echo "==> Pulando $(basename "$f") (redundante/fora de ordem conhecido)"
    continue
  fi
  run_sql_file "$f"
done < <(ls -1 "$MIG_DIR"/*.sql | sort)

echo "==> Migrações concluídas."

FIX_FILE="$(ls -1 "$MIG_DIR"/20260219_*LHS*INSCRICOES*.sql 2>/dev/null | head -n 1 || true)"
if [[ -n "$FIX_FILE" ]]; then
  echo "==> Rodando fix adicional LHS: $(basename "$FIX_FILE")"
  run_sql_file "$FIX_FILE"
else
  echo "==> Aviso: não encontrei migração fix do LHS (recomendado criar/commitar)."
fi