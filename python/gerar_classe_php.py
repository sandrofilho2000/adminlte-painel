import os
import re


def ler_create_table():
    print("Cole o CREATE TABLE MySQL abaixo.")
    print("Quando terminar, digite uma linha contendo apenas FIM e pressione Enter.")
    print()

    linhas = []

    while True:
        linha = input()
        if linha.strip().upper() == "FIM":
            break
        linhas.append(linha)

    return "\n".join(linhas)


def extrair_nome_tabela(sql):
    padrao = r"CREATE\s+TABLE\s+`?([a-zA-Z0-9_]+)`?"
    resultado = re.search(padrao, sql, re.IGNORECASE)

    if not resultado:
        raise ValueError("Não foi possível identificar o nome da tabela.")

    return resultado.group(1)


def extrair_colunas(sql):
    colunas = []

    linhas = sql.splitlines()

    palavras_ignoradas = (
        "constraint",
        "foreign",
        "primary",
        "unique",
        "key",
        "index",
        "references",
        "on ",
        ")",
    )

    for linha in linhas:
        linha_limpa = linha.strip().rstrip(",")

        if not linha_limpa:
            continue

        linha_minuscula = linha_limpa.lower()

        if linha_minuscula.startswith(palavras_ignoradas):
            continue

        if linha_minuscula.startswith("create table"):
            continue

        resultado = re.match(r"`?([a-zA-Z_][a-zA-Z0-9_]*)`?\s+", linha_limpa)

        if resultado:
            nome_coluna = resultado.group(1)
            if nome_coluna not in colunas:
                colunas.append(nome_coluna)

    return colunas


def montar_propriedades_php(colunas):
    propriedades = []

    for coluna in colunas:
        propriedades.append(f"    public ${coluna};")

    return "\n".join(propriedades)


def montar_colunas_php(colunas):
    linhas = []

    for coluna in colunas:
        linhas.append(f'            "{coluna}",')

    return "\n".join(linhas)


def montar_classe_php(nome_classe, nome_tabela, permissao, colunas):
    propriedades = montar_propriedades_php(colunas)
    colunas_php = montar_colunas_php(colunas)

    return f'''<?php

namespace Classes;

require_once BASE_PATH . '/includes/functions.php';

use PDO;
use DateTime;

class {nome_classe} extends ClasseBase
{{
{propriedades}

    protected $_tabela = array(
        'nome' => '{nome_tabela}',
        'schema' => 'portal',
        'chave_primaria' => array('id'),
        'colunas' => array(
{colunas_php}
        ),
        'permissao' => '{permissao}'
    );

    public function __construct()
    {{
        parent::__construct();
    }}
}}
'''


def formatar_nome_arquivo(nome_classe):
    return f"{nome_classe}.php"


def criar_arquivo_classe(nome_classe, conteudo_php):
    pasta_classes = os.path.join(os.getcwd(), "classes")
    os.makedirs(pasta_classes, exist_ok=True)

    nome_arquivo = formatar_nome_arquivo(nome_classe)
    caminho_arquivo = os.path.join(pasta_classes, nome_arquivo)

    with open(caminho_arquivo, "w", encoding="utf-8") as arquivo:
        arquivo.write(conteudo_php)

    return caminho_arquivo


def main():
    try:
        sql = ler_create_table()

        nome_classe = input("\nDigite o nome da classe PHP: ").strip()
        permissao = input("Digite o nome da permissão: ").strip()

        if not nome_classe:
            raise ValueError("O nome da classe é obrigatório.")

        nome_tabela = extrair_nome_tabela(sql)
        colunas = extrair_colunas(sql)

        if not colunas:
            raise ValueError("Nenhuma coluna foi encontrada no CREATE TABLE.")

        conteudo_php = montar_classe_php(
            nome_classe=nome_classe,
            nome_tabela=nome_tabela,
            permissao=permissao,
            colunas=colunas
        )

        caminho_arquivo = criar_arquivo_classe(nome_classe, conteudo_php)

        print("\nClasse criada com sucesso!")
        print(f"Arquivo gerado em: {caminho_arquivo}")

    except Exception as erro:
        print(f"\nErro: {erro}")


if __name__ == "__main__":
    main()
