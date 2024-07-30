$(document).ready(() => {
    const importSheetElement = document.createElement('div');
    importSheetElement.setAttribute('id', 'bigsheet');
    importSheetElement.setAttribute('style', 'display:none');
    importSheetElement.innerHTML = '<input type="file">' +
        '<button type="submit">Importar</button>';
    importSheetElement.querySelector('button').addEventListener('click', async e => {
        e.preventDefault();
        const file = importSheetElement.querySelector('input').files[0];
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
        }
    });
    document.getElementById('avaliacoes').parentElement.appendChild(importSheetElement);
});

const renderOccurrences = occurrences => {
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
            - ${occurrence.occurrence} <span style="color:#042f2b">("${occurrence.givenValue}")</span>`;
        rowLegendElement.appendChild(occurrenceElement);

        lastRowIndex = occurrence.rowIndex;
    });
    occurrencesElement.appendChild(occurrencesList);
    document.getElementById('bigsheet').appendChild(occurrencesElement);
};

const renderSavedRows = rows => {
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
            cellElement.innerHTML = value;
            rowElement.appendChild(cellElement);
        }
        savedRowsList.appendChild(rowElement);
    });
    savedRowsElement.appendChild(savedRowsList);
    document.getElementById('bigsheet').appendChild(savedRowsElement);
};
