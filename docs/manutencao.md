# Manutenção do perfil

Este repositório pertence somente ao perfil público `Sandro94/Sandro94`. Não publica nem altera os sites da Milagres Transparente.

## Conteúdo e identidade

- `README.md`: apresentação, links oficiais, stack verificada, plataforma em destaque e cartões.
- `banner.png`: composição original gerada com a ferramenta nativa de imagens, usando o símbolo oficial da Milagres como referência de marca. Paleta: #003153, #00C4F5, #DF9137 e branco.
- `assets/*.svg`: cartões vetoriais gerados a partir de dados públicos reais.
- `scripts/`: geração e validação, com módulos nativos de Node.js >=22; sem dependências de instalação.
- `.github/workflows/profile-cards.yml`: atualização diária às 11:17 UTC e execução manual em Actions. Alterações nos arquivos de apresentação/geração também iniciam a atualização.

A estrutura da apresentação foi inspirada nas categorias do perfil indicado pelo proprietário: apresentação, ferramentas, projetos e atividade. Biografia, texto e arte são próprios; não foram copiados nome, formação, projetos ou qualificações da referência.

A stack foi conferida no projeto principal: HTML, CSS, JavaScript, Node.js/ESM, PHP, Playwright, Git, GitHub Actions e Hostinger. Não foram acrescentadas ferramentas sem evidência de uso.

## Dados e automação

As estatísticas consultam a API pública do GitHub. Linguagens são somadas por bytes em repositórios públicos próprios, excluindo forks e este repositório de perfil. O calendário é consultado anonimamente no próprio GitHub e reproduz a atividade visível aos visitantes, sem detalhes de projetos privados. Se não houver linguagens públicas, o cartão informa isso; não inventa um ranking.

O workflow usa somente o `GITHUB_TOKEN` temporário da execução, com permissão de escrita limitada a este repositório, para salvar os três SVG. Não requer PAT nem acesso aos repositórios dos sites. O token não é publicado nos arquivos. Actions externas estão fixadas por SHA.

A atualização salva apenas cartões alterados e não cria commits vazios. O push feito com o token do workflow não inicia outro workflow de push; os caminhos dos cartões também ficam fora do filtro de execução. Se uma consulta falhar ou o formato do calendário mudar, o script falha antes de substituir os cartões existentes.

Comandos:

```sh
npm run stats
npm run check
```

A geração precisa de conexão com GitHub; a validação de arquivos é local. Para atualizar manualmente: Actions → Atualizar cartões do perfil → Run workflow. Os repositórios dos sites continuam privados e inalterados.

## Prompt do banner

Ferramenta utilizada: geração nativa de imagens (sem CLI/API com chave pessoal). Arte final: `banner.png`; referência de marca: símbolo oficial fornecido pela agência, sem utilizar a arte pessoal do perfil de referência.

```text
Use case: ads-marketing. Asset type: original horizontal GitHub profile banner for Sandro94, entrepreneur behind Milagres Transparente, Brazilian tourism and digital experiences. Create a polished, elegant editorial banner, approximately 1792 by 640 pixels, near 3:1 landscape. Predominant deep navy #003153 with darker navy edges, cyan #00C4F5, restrained gold #DF9137 and white. A sophisticated abstract ocean surface and flowing contour lines connect tourism to subtle digital network nodes, with one refined gold arc like a coastal route. Deep spacious background, no busy scenery, no people, no stock photography, no mockup or border. Very large impeccably readable modern sans serif white text SANDRO94 placed on the left within generous safe margins. Under it, medium large text exactly: 'Turismo · Tecnologia · Automação'. A small gold rule gives rhythm. On the right, use the supplied original Milagres symbol as a supporting brand emblem, preserving the blue waves, gold ribbon, sun and palms without redesigning its identity; integrate against the navy without a rectangle. Below the emblem, put the exact brand text in clean white: 'MILAGRES TRANSPARENTE'. All text must be spelled exactly with correct Portuguese accents, horizontally aligned and easy to read when scaled. Premium, calm, precise, original visual identity; never copy another person's GitHub banner. Avoid extra text, buttons, badges, charts, extra logos, tiny illegible text, exaggerated glows, watermarks. Reference image role: existing official brand emblem only; the overall banner is a brand-new original composition.
```

## Fontes de funcionamento

- [README especial de perfil](https://docs.github.com/en/account-and-profile/how-tos/profile-customization/managing-your-profile-readme).
- [Autenticação de workflows](https://docs.github.com/en/actions/tutorials/authenticate-with-github_token).
- [Prevenção de execuções recursivas](https://docs.github.com/en/actions/how-tos/write-workflows/choose-when-workflows-run/trigger-a-workflow).