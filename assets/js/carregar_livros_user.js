document.addEventListener("DOMContentLoaded", function () {
    async function fetchBookCover(isbn) {
        try {
            const response = await fetch(`https://www.googleapis.com/books/v1/volumes?q=isbn:${isbn}`);
            const data = await response.json();

            if (data.items && data.items[0].volumeInfo.imageLinks) {
                return data.items[0].volumeInfo.imageLinks.thumbnail;
            }
        } catch (error) {
            console.error("Erro ao buscar capa do livro:", error);
        }

        return "https://via.placeholder.com/128x186";
    }

    async function addToCart(isbn, quantity = 1) {
        try {
            const response = await fetch("assets/php/add_to_cart.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
                },
                body: `isbn=${encodeURIComponent(isbn)}&quantity=${encodeURIComponent(quantity)}`
            });

            const data = await response.json();

            if (data.status === "success") {
                const badge = document.querySelector(".cart-badge");

                if (badge) {
                    badge.textContent = data.cartCount;
                    badge.style.display = data.cartCount > 0 ? "block" : "none";
                }

                // 👇 Nada de alert — comportamento silencioso
            } else {
                console.error("Erro:", data.message);
            }
        } catch (error) {
            console.error("Erro ao adicionar ao carrinho:", error);
        }
    }

    async function loadBooks() {
        try {
            const response = await fetch("assets/php/listar_livros.php");

            if (!response.ok) {
                throw new Error("Erro na requisição: " + response.statusText);
            }

            const data = await response.json();
            const bookListContainer = document.getElementById("book-list");

            if (!bookListContainer) {
                return;
            }

            bookListContainer.innerHTML = "";

            if (data.error) {
                alert(data.error);
                return;
            }

            for (const book of data) {
                const coverUrl = await fetchBookCover(book.cod_isbn);

                const bookItem = document.createElement("div");
                bookItem.className = `book-item ${book.disponivel ? "" : "unavailable"}`;
                bookItem.setAttribute("data-isbn", book.cod_isbn);

                const bookLink = document.createElement("a");
                bookLink.href = `livro_detalhes.php?isbn=${encodeURIComponent(book.cod_isbn)}`;
                bookLink.className = "book-link";

                const bookCover = document.createElement("img");
                bookCover.src = coverUrl;
                bookCover.alt = `Capa do livro ${book.titulo}`;
                bookCover.className = "book-cover";

                const bookTitle = document.createElement("h5");
                bookTitle.textContent = book.titulo;

                const bookAuthor = document.createElement("p");
                bookAuthor.innerHTML = `<b>Autor: </b>${book.autor}`;

                bookLink.appendChild(bookCover);
                bookLink.appendChild(bookTitle);
                bookLink.appendChild(bookAuthor);

                bookItem.appendChild(bookLink);

                if (book.disponivel) {
                    const addButton = document.createElement("button");
                    addButton.type = "button";
                    addButton.className = "add-to-cart";
                    addButton.setAttribute("data-isbn", book.cod_isbn);
                    addButton.textContent = "Adicionar ao carrinho";

                    addButton.addEventListener("click", function () {
                        const isbn = this.dataset.isbn;

                        if (isbn) {
                            addToCart(isbn, 1);
                        }
                    });

                    bookItem.appendChild(addButton);
                } else {
                    const unavailableButton = document.createElement("button");
                    unavailableButton.className = "add-to-cart disabled";
                    unavailableButton.disabled = true;
                    unavailableButton.textContent = "Indisponível";

                    bookItem.appendChild(unavailableButton);
                }

                bookListContainer.appendChild(bookItem);
            }
        } catch (error) {
            console.error("Erro ao carregar livros:", error);
            alert("Erro ao carregar livros. Verifique o console para mais detalhes.");
        }
    }

    loadBooks();
});