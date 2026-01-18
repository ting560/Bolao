/* --- START OF FILE script.js --- */

const jogosListaElement = document.getElementById('jogos-lista');
const rodadaInfoElement = document.getElementById('rodada-info');
const mainErrorBox = document.getElementById('main-error-box');
const warningBox = document.getElementById('warning-box');
const loadingIndicatorElement = document.getElementById('loading-indicator');
const cacheNoticeElement = document.getElementById('cache-notice');
let isFetching = false;

function sanitizeHTML(str) {
    if (typeof str !== 'string') {
        return str;
    }
    const temp = document.createElement('div');
    temp.textContent = str;
    return temp.innerHTML;
}

function renderJogoItem(jogo) {
    const jogoId = sanitizeHTML(jogo.id || Date.now());
    const time1Abbrev = sanitizeHTML(jogo.team1?.abbrev || 'T1');
    const time2Abbrev = sanitizeHTML(jogo.team2?.abbrev || 'T2');
    const time1Logo = sanitizeHTML(jogo.team1?.logo || window.placeholderLogoUrl);
    const time2Logo = sanitizeHTML(jogo.team2?.logo || window.placeholderLogoUrl);
    const horario = sanitizeHTML(jogo.datetime || 'N/A');
    const score = sanitizeHTML(jogo.score || '- x -');
    const status = sanitizeHTML(jogo.status || 'N/A');
    const linkTransmissao = sanitizeHTML(jogo.linkTransmissao || '');
    const textoLinkTransmissao = sanitizeHTML(jogo.textoLinkTransmissao || 'Ver Transmissão');

    let statusClass = '';
    if (status.toLowerCase().includes('ao vivo')) {
        statusClass = 'ao-vivo';
    } else if (status.toLowerCase().includes('em breve')) {
        statusClass = 'em-breve';
    } else if (status.toLowerCase().includes('encerrado')) {
        statusClass = 'encerrado';
    }

    const placarParts = score.split('x');
    const placarCasa = sanitizeHTML(placarParts[0] ? placarParts[0].trim() : '-');
    const placarFora = sanitizeHTML(placarParts[1] ? placarParts[1].trim() : '-');

    return `
        <li class="jogo-item" data-id="${jogoId}">
            <div class="jogo-header" data-jogo-id-header="${jogoId}">
                <div class="jogo-top-info">
                    <span class="data-horario">${horario}</span>
                </div>
                <div class="jogo-score-line">
                    <span class="time-abbrev time1-abbrev">${time1Abbrev}</span>
                    <div class="time-logo time1-logo">
                        <img src="${time1Logo}" alt="Logo ${time1Abbrev}" onerror="this.onerror=null; this.src='${window.placeholderLogoUrl}';">
                    </div>
                    <span class="placar-gol">${placarCasa}</span>
                    <span class="placar-x">X</span>
                    <span class="placar-gol">${placarFora}</span>
                    <div class="time-logo time2-logo">
                        <img src="${time2Logo}" alt="Logo ${time2Abbrev}" onerror="this.onerror=null; this.src='${window.placeholderLogoUrl}';">
                    </div>
                    <span class="time-abbrev time2-abbrev">${time2Abbrev}</span>
                </div>
                <div class="jogo-bottom-info">
                    <div class="status ${statusClass}">${status}</div>
                    ${linkTransmissao ? `<a href="${linkTransmissao}" target="_blank" class="transmissao-link">${textoLinkTransmissao}</a>` : ''}
                </div>
            </div>
            <div class="palpites-container" id="palpites-${jogoId}" style="display: none;">
                <div class="palpites-titulo">Palpites dos Participantes</div>
                <div class="palpites-lista"></div>
            </div>
        </li>
    `;
}

async function carregarPalpites(rodadaNome, jogoId, container) {
    const palpitesListaElement = container.querySelector(`#palpites-${jogoId} .palpites-lista`);
    if (!palpitesListaElement) return;
    palpitesListaElement.innerHTML = '<div style="text-align: center; padding: 20px; color: #666;">Carregando palpites...</div>';
    try {
        const response = await fetch(`buscar_palpites.php?rodada=${encodeURIComponent(rodadaNome)}&jogo_id=${encodeURIComponent(jogoId)}`);
        if (!response.ok) {
             const errorText = await response.text();
             throw new Error(`HTTP error! status: ${response.status}. Detalhes: ${errorText.substring(0, 100)}...`);
        }
        const text = await response.text();
        let palpites = [];
        if (text) {
             try {
                 palpites = JSON.parse(text);
             } catch (jsonError) {
                  console.error('Erro ao parsear JSON de palpites:', jsonError, 'Texto recebido:', text);
                  throw new Error('Resposta inválida do servidor.');
             }
        }
        let html = '';
        if (palpites && Array.isArray(palpites) && palpites.length > 0) {
            palpites.sort((a, b) => {
                const nomeA = a.apostador ? String(a.apostador) : '';
                const nomeB = b.apostador ? String(b.apostador) : '';
                return nomeA.localeCompare(nomeB);
            });
            html = palpites.map(p => {
                const apostadorNome = sanitizeHTML(p.apostador || 'Desconhecido');
                const palpiteCasa = sanitizeHTML(p.palpiteCasa !== undefined ? p.palpiteCasa : '?');
                const palpiteFora = sanitizeHTML(p.palpiteFora !== undefined ? p.palpiteFora : '?');
                return `
                    <div class="palpite-item">
                        <div style="font-weight: 500; color: #333;">${apostadorNome}</div>
                        <div style="font-weight: bold; font-size: 16px; color: #1a73e8;">${palpiteCasa} x ${palpiteFora}</div>
                    </div>
                `;
            }).join('');
        } else {
            html = '<div style="text-align: center; padding: 30px; color: #666;">Nenhum palpite registrado para este jogo.</div>';
        }
        palpitesListaElement.innerHTML = html;
    } catch (error) {
        console.error('Erro ao carregar palpites:', error);
        palpitesListaElement.innerHTML = '<div style="text-align: center; padding: 20px; color: #d32f2f;">Erro ao carregar palpites: ' + sanitizeHTML(error.message) + '</div>';
    }
}

function togglePalpitesVisibilidade(jogoId) {
    const palpitesDiv = document.getElementById(`palpites-${jogoId}`);
    if (!palpitesDiv) return;
    document.querySelectorAll('.palpites-container').forEach(container => {
        if (container.id !== `palpites-${jogoId}`) container.style.display = 'none';
    });
    const isVisible = palpitesDiv.style.display !== 'none';
    if (isVisible) {
        palpitesDiv.style.display = 'none';
    } else {
        palpitesDiv.style.display = 'block';
        const rodadaAtualElement = document.getElementById('rodada-info');
        const rodadaAtual = rodadaAtualElement ? rodadaAtualElement.textContent.trim() : 'Rodada Atual';
        carregarPalpites(rodadaAtual, jogoId, palpitesDiv.closest('.jogo-item'));
    }
}

if (jogosListaElement) {
    jogosListaElement.addEventListener('click', (event) => {
        const targetHeader = event.target.closest('.jogo-header');
        if (targetHeader && targetHeader.dataset.jogoIdHeader) {
            togglePalpitesVisibilidade(targetHeader.dataset.jogoIdHeader);
        }
    });
}

window.idsRenderizados = window.idsRenderizados || [];

function displayData(data) {
    rodadaInfoElement.textContent = sanitizeHTML(data.rodada || 'Rodada Indisponível');
    mainErrorBox.style.display = 'none';
    mainErrorBox.textContent = '';
    warningBox.style.display = 'none';
    warningBox.textContent = '';
    cacheNoticeElement.textContent = '';
    if (data.error) {
        if (!data.jogos || data.jogos.length === 0) {
            mainErrorBox.textContent = sanitizeHTML(data.error);
            mainErrorBox.style.display = 'block';
            jogosListaElement.innerHTML = '<li style="text-align:center; padding:20px; color:#606770;">' + sanitizeHTML(data.error) + '</li>';
        } else {
            warningBox.textContent = sanitizeHTML(data.error);
            warningBox.style.display = 'block';
        }
    }
    if (data.jogos && Array.isArray(data.jogos) && data.jogos.length > 0) {
        jogosListaElement.innerHTML = data.jogos.map(renderJogoItem).join('');
        window.idsRenderizados = data.jogos.map(j => j.id);
    } else if (!data.error) {
        jogosListaElement.innerHTML = '<li style="text-align:center; padding:20px; color:#606770;">Nenhum jogo programado.</li>';
    }
    if (data.cache_source) {
        cacheNoticeElement.textContent = '';
    } else {
         cacheNoticeElement.textContent = '';
    }
}

async function actualizarClassificacao() {
    try {
        const response = await fetch('palpites.php?ajax=1&t=' + new Date().getTime(), { cache: 'no-store' });
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`Erro ao buscar classificação (HTTP ${response.status}): ${errorText.substring(0, 200)}...`);
        }
        const data = await response.json();
        const tabelaClassificacaoBody = document.querySelector('#tabela-classificacao-body');
        if (tabelaClassificacaoBody && data.apostadores && Array.isArray(data.apostadores)) {
            let html = '';
            if (data.apostadores.length > 0) {
                // Ajuste para mostrar novas colunas se decidir adicioná-las à index.php
                // Por enquanto, mantém simples (Pos, Apostador, Pontos)
                html = data.apostadores.map((item, index) => `
                    <tr>
                        <td>${index + 1}º</td>
                        <td>${sanitizeHTML(item.nome || 'Desconhecido')}</td>
                        <td><strong>${sanitizeHTML(item.pontuacao !== undefined ? item.pontuacao : '-')}</strong></td>
                    </tr>
                `).join('');
            } else {
                html = '<tr><td colspan="3">Nenhum ponto registrado ainda.</td></tr>';
            }
            tabelaClassificacaoBody.innerHTML = html;
        } else if (tabelaClassificacaoBody) {
             console.error('Dados de apostadores inválidos na resposta da classificação:', data);
             tabelaClassificacaoBody.innerHTML = '<tr><td colspan="3">Erro ao carregar classificação ou dados ausentes.</td></tr>';
        }
    } catch (error) {
        console.error('Erro ao atualizar classificação:', error);
        const tabelaClassificacaoBody = document.querySelector('#tabela-classificacao-body');
        if (tabelaClassificacaoBody) {
            tabelaClassificacaoBody.innerHTML = `<tr><td colspan="3">Erro ao carregar classificação: ${sanitizeHTML(error.message)}</td></tr>`;
        }
        if (warningBox.style.display === 'none' && mainErrorBox.style.display === 'none') {
             warningBox.textContent = `Falha ao carregar classificação: ${sanitizeHTML(error.message)}`;
             warningBox.style.display = 'block';
        } else {
             console.log("Aviso de classificação não exibido pois já há outro aviso na tela.");
        }
    }
}

function atualizarDadosCompletos() {
    if (isFetching) return;
    isFetching = true;
    loadingIndicatorElement.style.display = 'block';
    fetch('?ajax=1&t=' + new Date().getTime(), { cache: 'no-store' })
        .then(response => {
             if (!response.ok) {
                 return response.text().then(text => {
                      throw new Error(`Erro na requisição de jogos (HTTP ${response.status}): ${text.substring(0, 200)}...`);
                 });
             }
             return response.json();
        })
        .then(data => {
            displayData(data);
            return actualizarClassificacao();
        })
        .catch(error => {
            console.error('Erro geral na atualização:', error);
            const fallbackErrorMsg = `Falha ao atualizar dados: ${sanitizeHTML(error.message)}.`;
            const temJogosVisiveis = jogosListaElement.children.length > 0 && Array.from(jogosListaElement.children).some(child => child.classList.contains('jogo-item'));
            if (temJogosVisiveis) {
                warningBox.textContent = fallbackErrorMsg;
                warningBox.style.display = 'block';
            } else {
                 mainErrorBox.textContent = fallbackErrorMsg;
                 mainErrorBox.style.display = 'block';
                 jogosListaElement.innerHTML = '<li style="text-align:center; padding:20px; color:#606770;">' + fallbackErrorMsg + '</li>';
            }
             const tabelaClassificacaoBody = document.querySelector('#tabela-classificacao-body');
             if (tabelaClassificacaoBody && tabelaClassificacaoBody.innerHTML.indexOf("Carregando") > -1) {
                  tabelaClassificacaoBody.innerHTML = `<tr><td colspan="3">Erro ao carregar classificação.</td></tr>`;
             }
        })
        .finally(() => {
            isFetching = false;
            loadingIndicatorElement.style.display = 'none';
            setTimeout(atualizarDadosCompletos, 60000);
        });
}

if (window.initialData && typeof window.initialData === 'object') {
    displayData(window.initialData);
    actualizarClassificacao();
    setTimeout(atualizarDadosCompletos, 60000);
} else {
    atualizarDadosCompletos();
}

document.querySelector('.atualizar-link[href^="?update=1"]').addEventListener('click', function(event) {
    event.preventDefault();
    atualizarDadosCompletos();
});
/* --- END OF FILE script.js --- */