import sys
from pathlib import Path

from PIL import Image
from rembg import new_session, remove


SESSAO_REMOCAO = new_session("u2netp")
TAMANHO_MAXIMO = (1600, 1600)


def remover_fundo(caminho_entrada, caminho_saida):
    entrada = Path(caminho_entrada)
    saida = Path(caminho_saida)

    if not entrada.is_file():
        raise FileNotFoundError("Imagem de entrada nao encontrada.")

    with Image.open(entrada) as imagem:
        imagem.thumbnail(TAMANHO_MAXIMO, Image.LANCZOS)
        imagem_sem_fundo = remove(imagem, session=SESSAO_REMOCAO)
        imagem_sem_fundo.save(saida, "PNG")


def principal():
    if len(sys.argv) != 3:
        print("Uso: python remover_fundo.py entrada saida", file=sys.stderr)
        return 1

    try:
        remover_fundo(sys.argv[1], sys.argv[2])
        return 0
    except Exception as erro:
        print(f"Erro ao remover fundo: {erro}", file=sys.stderr)
        return 1


if __name__ == "__main__":
    sys.exit(principal())
