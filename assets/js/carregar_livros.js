document.addEventListener("DOMContentLoaded", function () {
    const bookListContainer = document.getElementById("book-list");
    const defaultCover = "https://via.placeholder.com/128x186";

    function escapeHtml(value) {
        const div = document.createElement("div");
        div.textContent = value ?? "";
        return div.innerHTML;
    }

    async function fetchBookCover(isbn) {
        try {
            const response = await fetch(`https://www.googleapis.com/books/v1/volumes?q=isbn:${encodeURIComponent(isbn)}`);

            if (!response.ok) {
                throw new Error(`Erro HTTP ${response.status}`);
            }

            const data = await response.json();
            return data.items?.[0]?.volumeInfo?.imageLinks?.thumbnail || defaultCover;
        } catch (error) {
            console.error(`Erro ao buscar capa do livro com ISBN ${isbn}:`, error);
            return defaultCover;
        }
    }

    function createBookElement(book, coverUrl) {
        const bookItem = document.createElement("div");
        bookItem.className = "book-item";
        bookItem.dataset.isbn = book.cod_isbn;

        bookItem.innerHTML = `
            <img src="${escapeHtml(coverUrl)}" alt="Capa do livro ${escapeHtml(book.titulo)}" class="book-cover">
            <h5>${escapeHtml(book.titulo)}</h5>
            <p><b>Autor: </b>${escapeHtml(book.autor)}</p>

            <form method="POST" action="assets/php/remover_livro.php">
                <input type="hidden" name="isbn" value="${escapeHtml(book.cod_isbn)}">
                <button type="submit" class="remove-book">Remover</button>
            </form>
        `;

        return bookItem;
    }

    async function loadBooks() {
        if (!bookListContainer) {
            return;
        }

        try {
            const response = await fetch("assets/php/listar_livros.php");

            if (!response.ok) {
                throw new Error("Erro na requisição: " + response.statusText);
            }

            const data = await response.json();
            bookListContainer.innerHTML = "";

            if (data.error) {
                console.error(data.error);
                return;
            }

            if (!Array.isArray(data) || data.length === 0) {
                bookListContainer.innerHTML = "<p>Não existem livros registados.</p>";
                return;
            }

            const covers = await Promise.all(
                data.map(book => fetchBookCover(book.cod_isbn))
            );

            const fragment = document.createDocumentFragment();

            data.forEach((book, index) => {
                const bookElement = createBookElement(book, covers[index]);
                fragment.appendChild(bookElement);
            });

            bookListContainer.appendChild(fragment);
        } catch (error) {
            console.error("Erro ao carregar livros:", error);
            bookListContainer.innerHTML = "<p>Erro ao carregar livros.</p>";
        }
    }

    loadBooks();
});