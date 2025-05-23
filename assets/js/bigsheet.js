$(document).ready(() => {
    const importSheetElement = document.createElement('div');
    importSheetElement.setAttribute('id', 'bigsheet');
    importSheetElement.classList.add('aba-content');
    if (window.location.hash !== '#tab=bigsheet') {
        importSheetElement.setAttribute('style', 'display:none');
    }
    importSheetElement.innerHTML = '<div style="display: flex;">' +
        '<input type="file">' +
        '<button type="submit" data-text="Importar" disabled>Importar</button>' +
        '<a href="/bigsheet/templateSheet" class="btn btn-default" style="margin-left: auto;">Baixar modelo</a>' +
    '</div>';

    const loadingElement = document.createElement('div');
    loadingElement.setAttribute('id', 'loading');
    loadingElement.style.display = 'none';
    loadingElement.innerHTML = `<img src=${MapasCulturais.spinnerURL} alt="Carregando..." />`;
    importSheetElement.appendChild(loadingElement);

    const validateButton = document.createElement('button');
    validateButton.id = 'validateSpreadsheet';
    validateButton.classList.add('btn', 'btn-default');
    validateButton.innerText = 'Validar planilha';
    validateButton.dataset.text = 'Validar planilha';
    importSheetElement.appendChild(validateButton);

    importSheetElement.querySelector('input').addEventListener('change', e => {
        if(e.target.files.length)
            importSheetElement.querySelector('button').disabled = false;
    });
    importSheetElement.querySelector('button[type=submit]').addEventListener('click', async e => {
        e.preventDefault();
        toggleLoading(e.target, loadingElement);

        const fileInput = importSheetElement.querySelector('input')
        const file = fileInput.files[0];
        fileInput.value = '';

        const body = new FormData();
        body.append('spreadsheet', file);
        body.append('entity', 'Opinion');

        try {
            const response = await fetch(MapasCulturais.createUrl('bigsheet', 'import'), {
                method: 'POST',
                body: body
            });

            if(!response.ok)
                return console.error(response);

            const data = await response.json();
            renderOccurrences(data.occurrences);
            renderSavedRows(data.rowsSaved);

        } catch (e) {
            console.error(e);
        } finally {
            toggleLoading(e.target, loadingElement);
        }
    });
    validateButton.addEventListener('click', async e => {
        e.preventDefault();
        toggleLoading(e.target, loadingElement);
        document.getElementById('occurrences')?.remove();
        document.getElementById('errorMessage')?.remove();
        document.getElementById('noOccurrences')?.remove();

        const url = MapasCulturais.createUrl('bigsheet', 'validateSpreadsheet');

        const fileInput = importSheetElement.querySelector('input')
        const file = fileInput.files[0];

        fileInput.value = '';
        const body = new FormData();
        body.append('spreadsheet', file);

        body.append('entity', 'Opinion');

        try {
            const response = await fetch(url, {
                method: 'POST',
                body: body,
            });

            if (!response.ok) {
                const errorMessage = document.createElement('div');
                errorMessage.id = 'errorMessage';
                errorMessage.innerText = 'Houve um erro interno. Favor, tentar novamente.';
                validateButton.after(errorMessage);
                console.error(response);
                return;
            }

            const data = await response.json();
            if (data?.occurrences.length > 0) {
                renderOccurrences(data.occurrences);
                return;
            }

            const noOccurrences = document.createElement('div');
            noOccurrences.id = 'noOccurrences';
            noOccurrences.classList.add('alert', 'success');
            noOccurrences.innerText = 'Sem problemas de formatação encontrados na validação da planilha.';
            validateButton.after(noOccurrences);
        } catch (e) {
            console.error(e);
        } finally {
            toggleLoading(e.target, loadingElement);
        }
    });

    document.getElementById('avaliacoes').parentElement.appendChild(importSheetElement);

    fetch(MapasCulturais.createUrl('bigsheet', 'history'))
        .then(response => response.json())
        .then(data => renderImportHistory(data))
        .catch(err => console.error(err));
});

const renderImportHistory = history => {
    document.getElementById('importHistory')?.remove();
    const importHistory = document.createElement('table');
    importHistory.id = 'importHistory';
    importHistory.innerHTML = '<tr><th>id</th><th>Data da importação</th><th>Quantidade de linhas</th><th>Usuário</th>'

    for (const item of history) {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${item.id}</td><td>${item.date.date}</td><td>${item.rowsAmount}</td><td>${item.user.userName}</td>`;

        importHistory.appendChild(tr);
    }

    document.getElementById('bigsheet').appendChild(importHistory);
}

const renderOccurrences = occurrences => {
    document.getElementById('occurrences')?.remove();
    document.getElementById('importHistory')?.remove();
    const occurrencesElement = document.createElement('div');
    occurrencesElement.setAttribute('id', 'occurrences');
    occurrencesElement.innerHTML = '<h2>Ocorrências</h2>';
    const occurrencesList = document.createElement('div');
    occurrencesList.setAttribute('id', 'occurrences-list');
    let lastRowIndex = 0;
    let rowLegendElement = document.createElement('div')
    occurrences.forEach(occurrence => {
        if (occurrence.rowIndex !== lastRowIndex) {
            rowLegendElement = document.createElement('div');
            rowLegendElement.innerHTML = `<strong>Linha ${occurrence.rowIndex}</strong>`;
            occurrencesList.appendChild(rowLegendElement);
        }
        const occurrenceElement = document.createElement('div');
        occurrenceElement.style.color = 'red';
        occurrenceElement.innerHTML = `<strong>${occurrence.columnIndex+occurrence.rowIndex}</strong>
            - ${occurrence.occurrence ?? occurrence.message} 
            <span style="color:#042f2b">("${occurrence.givenValue ?? occurrence.value}")</span>`;
        rowLegendElement.appendChild(occurrenceElement);

        lastRowIndex = occurrence.rowIndex;
    });
    occurrencesElement.appendChild(occurrencesList);

    const importHistory = document.getElementById('importHistory');
    if (importHistory) {
        importHistory.after(occurrencesElement);
    } else {
        document.getElementById('bigsheet').appendChild(occurrencesElement);
    }
};

const renderSavedRows = rows => {
    document.getElementById('saved-rows')?.parentElement.remove();
    const containerElement = document.createElement('div');
    containerElement.innerHTML = '<h2>Dados salvos</h2>';
    const savedRowsElement = document.createElement('table');
    savedRowsElement.setAttribute('id', 'saved-rows');
    const savedRowsList = document.createElement('tbody');
    savedRowsList.setAttribute('id', 'saved-rows-list');
    const rowsHeader = document.createElement('thead');
    let rowsHeaderHTML = '<tr>';
    for (const key of Object.keys(rows[0])) {
        if (key === 'registration') continue;
        rowsHeaderHTML += `<th>${key}</th>`;
    }
    rowsHeaderHTML += '</tr>';
    rowsHeader.innerHTML = rowsHeaderHTML;
    savedRowsElement.appendChild(rowsHeader);
    rows.forEach(row => {
        const rowElement = document.createElement('tr');
        for (const [key, value] of Object.entries(row)) {
            if (key === 'registration') continue;
            const cellElement = document.createElement('td');
            cellElement.innerHTML =  value?.date ?? value;
            rowElement.appendChild(cellElement);
        }
        savedRowsList.appendChild(rowElement);
    });
    savedRowsElement.appendChild(savedRowsList);
    containerElement.appendChild(savedRowsElement);
    document.getElementById('bigsheet').appendChild(containerElement);
};

const toggleLoading = (buttonElement, loadingElement) => {
    if (loadingElement.style.display === 'none') {
        buttonElement.disabled = true;
        buttonElement.innerHTML = 'Importando...';
        loadingElement.style.display = 'block';
    } else {
        buttonElement.disabled = false;
        buttonElement.innerHTML = buttonElement.dataset.text;
        loadingElement.style.display = 'none';
    }
};
