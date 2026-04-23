document.addEventListener('DOMContentLoaded', function () {
    const entregaModal = document.getElementById('entregaModal');
    const closeEntregaModal = document.getElementById('closeEntregaModal');
    const btnCancelarEntrega = document.getElementById('btnCancelarEntrega');
    const btnConfirmarEntrega = document.getElementById('btnConfirmarEntrega');

    const devolucaoModal = document.getElementById('devolucaoModal');
    const closeDevolucaoModal = document.getElementById('closeDevolucaoModal');
    const btnCancelarDevolucao = document.getElementById('btnCancelarDevolucao');
    const btnConfirmarDevolucao = document.getElementById('btnConfirmarDevolucao');

    let requisicaoId = null;
    let devolucaoId = null;

    const PLACEHOLDER_CAPA = 'https://via.placeholder.com/128x186';

    async function obterCapaLivro(isbn) {
        try {
            const response = await fetch(`https://www.googleapis.com/books/v1/volumes?q=isbn:${encodeURIComponent(isbn)}`);

            if (!response.ok) {
                throw new Error('Erro ao obter capa do livro.');
            }

            const googleData = await response.json();
            return googleData.items?.[0]?.volumeInfo?.imageLinks?.thumbnail || PLACEHOLDER_CAPA;
        } catch (error) {
            console.error('Erro ao buscar capa do livro:', error);
            return PLACEHOLDER_CAPA;
        }
    }

    function abrirDialog(modal) {
        if (!modal) return;

        if (typeof modal.showModal === 'function') {
            modal.showModal();
        } else {
            modal.setAttribute('open', 'open');
        }
    }

    function fecharDialog(modal) {
        if (!modal) return;

        if (typeof modal.close === 'function') {
            modal.close();
        } else {
            modal.removeAttribute('open');
        }
    }

    async function abrirModalEntrega(id, isbn) {
        try {
            requisicaoId = id;

            const response = await fetch(`assets/php/buscar_livro.php?isbn=${encodeURIComponent(isbn)}`);

            if (!response.ok) {
                throw new Error('Erro ao carregar os dados do livro.');
            }

            const data = await response.json();
            const capa = await obterCapaLivro(isbn);

            document.getElementById('tituloLivro').textContent = data.titulo || '';
            document.getElementById('autorLivro').textContent = data.autor || '';
            document.getElementById('isbnLivro').textContent = isbn || '';
            document.getElementById('capaLivro').src = capa;

            abrirDialog(entregaModal);
        } catch (error) {
            console.error('Erro ao abrir modal de entrega:', error);
        }
    }

    async function abrirModalDevolucao(button) {
        try {
            devolucaoId = button.dataset.id;
            const isbn = button.dataset.isbn || '';
            const titulo = button.dataset.titulo || '';
            const autor = button.dataset.autor || '';
            const capa = await obterCapaLivro(isbn);

            document.getElementById('tituloLivroDevolucao').textContent = titulo;
            document.getElementById('autorLivroDevolucao').textContent = autor;
            document.getElementById('isbnLivroDevolucao').textContent = isbn;
            document.getElementById('capaLivroDevolucao').src = capa;

            abrirDialog(devolucaoModal);
        } catch (error) {
            console.error('Erro ao abrir modal de devolução:', error);
        }
    }

    document.querySelectorAll('.entregar-livro').forEach((btn) => {
        btn.addEventListener('click', function () {
            abrirModalEntrega(this.dataset.id, this.dataset.isbn);
        });
    });

    document.querySelectorAll('.btn-confirmar-devolucao').forEach((btn) => {
        btn.addEventListener('click', function () {
            abrirModalDevolucao(this);
        });
    });

    if (closeEntregaModal) {
        closeEntregaModal.addEventListener('click', () => fecharDialog(entregaModal));
    }

    if (btnCancelarEntrega) {
        btnCancelarEntrega.addEventListener('click', () => fecharDialog(entregaModal));
    }

    if (btnConfirmarEntrega) {
        btnConfirmarEntrega.addEventListener('click', async () => {
            if (!requisicaoId) {
                fecharDialog(entregaModal);
                return;
            }

            try {
                const response = await fetch(`assets/php/entregar_livro.php?id=${encodeURIComponent(requisicaoId)}`);

                if (!response.ok) {
                    throw new Error('Erro ao confirmar entrega.');
                }

                await response.json().catch(() => null);
                location.reload();
            } catch (error) {
                console.error('Erro ao entregar livro:', error);
                location.reload();
            } finally {
                fecharDialog(entregaModal);
            }
        });
    }

    if (closeDevolucaoModal) {
        closeDevolucaoModal.addEventListener('click', () => fecharDialog(devolucaoModal));
    }

    if (btnCancelarDevolucao) {
        btnCancelarDevolucao.addEventListener('click', () => fecharDialog(devolucaoModal));
    }

    if (btnConfirmarDevolucao) {
        btnConfirmarDevolucao.addEventListener('click', () => {
            if (devolucaoId) {
                window.location.href = `assets/php/concluir_devolucao.php?id=${encodeURIComponent(devolucaoId)}`;
            }

            fecharDialog(devolucaoModal);
        });
    }

    [entregaModal, devolucaoModal].forEach((modal) => {
        if (!modal) return;

        modal.addEventListener('click', function (event) {
            const rect = this.getBoundingClientRect();
            const clickedInside =
                event.clientX >= rect.left &&
                event.clientX <= rect.right &&
                event.clientY >= rect.top &&
                event.clientY <= rect.bottom;

            if (!clickedInside) {
                fecharDialog(this);
            }
        });
    });

    document.querySelectorAll('tr[data-prazo-ms]').forEach((row) => {
        const prazoMs = parseInt(row.dataset.prazoMs, 10);

        if (!prazoMs) {
            return;
        }

        if (Date.now() > prazoMs) {
            row.classList.add('prazo-expirado');

            const btn = row.querySelector('.btn-confirmar-devolucao');
            if (btn) {
                btn.disabled = true;
                btn.classList.add('prazo-expirado');
                btn.textContent = 'Prazo Expirado';
            }
        }
    });
});