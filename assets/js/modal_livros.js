document.addEventListener("DOMContentLoaded", function () {
    const button = document.getElementById("openModal");
    const modal = document.querySelector("dialog.modal");
    const closeModal = document.getElementById("closeModal");
    const saveBook = document.getElementById("saveBook");
    const viewFullTextBtn = document.getElementById("viewFullText");
    const textModal = document.getElementById("textModal");
    const closeTextModalBtn = document.getElementById("closeTextModal");
    const saveTextModalBtn = document.getElementById("saveTextModal");
    const fullTextContent = document.getElementById("fullTextContent");

    const textarea = document.getElementById("summary");
    const isbnInput = document.getElementById("isbn");
    const bookTitleInput = document.getElementById("title");
    const bookAuthorInput = document.getElementById("author");
    const bookEditionInput = document.getElementById("edition");
    const bookPagesInput = document.getElementById("numero_paginas");
    const bookImage = document.getElementById("bookImage");
    const quantityInput = document.getElementById("quantity");
    const bookForm = document.getElementById("bookForm");

    function resetForm() {
        if (isbnInput) isbnInput.value = "";
        if (bookTitleInput) bookTitleInput.value = "";
        if (bookAuthorInput) bookAuthorInput.value = "";
        if (bookEditionInput) bookEditionInput.value = "";
        if (bookPagesInput) bookPagesInput.value = "";
        if (textarea) textarea.value = "";
        if (bookImage) bookImage.src = "https://via.placeholder.com/128x186";
        if (quantityInput) quantityInput.value = 1;
    }

    async function fetchBookDetails(isbn) {
        try {
            const response = await fetch(`https://www.googleapis.com/books/v1/volumes?q=isbn:${encodeURIComponent(isbn)}`);
            const data = await response.json();

            if (data.totalItems > 0 && data.items && data.items[0] && data.items[0].volumeInfo) {
                const book = data.items[0].volumeInfo;

                if (bookTitleInput) bookTitleInput.value = book.title || "";
                if (bookAuthorInput) bookAuthorInput.value = Array.isArray(book.authors) ? book.authors.join(", ") : "";
                if (bookEditionInput) bookEditionInput.value = book.publishedDate || "";
                if (bookPagesInput) bookPagesInput.value = book.pageCount || "";
                if (textarea) textarea.value = book.description || "";

                if (book.imageLinks && book.imageLinks.thumbnail && bookImage) {
                    bookImage.src = book.imageLinks.thumbnail;
                } else if (bookImage) {
                    bookImage.src = "https://via.placeholder.com/128x186";
                }
            }
        } catch (error) {
            console.error("Erro ao buscar os detalhes do livro:", error);
        }
    }

    async function saveBookHandler() {
        if (
            !isbnInput ||
            !bookTitleInput ||
            !bookAuthorInput ||
            !bookEditionInput ||
            !bookPagesInput ||
            !textarea ||
            !quantityInput
        ) {
            return;
        }

        const isbn = isbnInput.value.trim();
        const title = bookTitleInput.value.trim();
        const author = bookAuthorInput.value.trim();
        const edition = bookEditionInput.value.trim();
        const pages = bookPagesInput.value.trim();
        const summary = textarea.value.trim();
        const quantity = quantityInput.value.trim();

        if (!isbn || !title || !author || !edition || !pages || !summary || !quantity) {
            return;
        }

        const formData = new FormData();
        formData.append("isbn", isbn);
        formData.append("title", title);
        formData.append("edition", edition);
        formData.append("author", author);
        formData.append("numero_paginas", pages);
        formData.append("quantity", quantity);
        formData.append("summary", summary);

        try {
            const response = await fetch("./assets/php/captar_livro.php", {
                method: "POST",
                body: formData
            });

            if (!response.ok) {
                throw new Error("Falha ao guardar o livro.");
            }

            if (modal) {
                modal.close();
            }

            resetForm();

            if (typeof loadBooks === "function") {
                loadBooks();
            } else {
                window.location.reload();
            }
        } catch (error) {
            console.error("Erro ao guardar o livro:", error);
        }
    }

    if (button && modal) {
        button.addEventListener("click", function () {
            resetForm();
            modal.showModal();
        });
    }

    if (closeModal && modal) {
        closeModal.addEventListener("click", function () {
            modal.close();
            resetForm();
        });
    }

    if (bookForm) {
        bookForm.addEventListener("submit", function (e) {
            e.preventDefault();
            saveBookHandler();
        });
    }

    if (isbnInput) {
        isbnInput.addEventListener("input", function () {
            const isbn = isbnInput.value.trim();

            if (isbn.length === 10 || isbn.length === 13) {
                fetchBookDetails(isbn);
            } else {
                if (bookTitleInput) bookTitleInput.value = "";
                if (bookAuthorInput) bookAuthorInput.value = "";
                if (bookEditionInput) bookEditionInput.value = "";
                if (bookPagesInput) bookPagesInput.value = "";
                if (textarea) textarea.value = "";
                if (bookImage) bookImage.src = "https://via.placeholder.com/128x186";
                if (quantityInput) quantityInput.value = 1;
            }
        });
    }

    if (viewFullTextBtn && textModal && fullTextContent && textarea) {
        viewFullTextBtn.addEventListener("click", function () {
            const text = textarea.value.trim();

            if (!text) {
                return;
            }

            fullTextContent.innerHTML = "";

            const editableTextarea = document.createElement("textarea");
            editableTextarea.value = text;
            editableTextarea.id = "editableTextarea";
            editableTextarea.style.width = "100%";
            editableTextarea.style.height = "200px";
            editableTextarea.style.resize = "none";

            fullTextContent.appendChild(editableTextarea);
            textModal.showModal();
        });
    }

    if (saveTextModalBtn && textModal && textarea) {
        saveTextModalBtn.addEventListener("click", function () {
            const editableTextarea = document.getElementById("editableTextarea");

            if (editableTextarea) {
                textarea.value = editableTextarea.value;
            }

            textModal.close();
        });
    }

    if (closeTextModalBtn && textModal) {
        closeTextModalBtn.addEventListener("click", function (e) {
            e.preventDefault();
            textModal.close();
        });
    }
});