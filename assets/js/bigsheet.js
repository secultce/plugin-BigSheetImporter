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
    const occurrencesList = document.createElement('ul');
    occurrences.forEach(occurrence => {
        const occurrenceElement = document.createElement('li');
        occurrenceElement.style.color = 'red';
        occurrenceElement.innerHTML = `<strong>${occurrence.columnIndex+occurrence.rowIndex}</strong>
            - ${occurrence.occurrence} <span style="color:#042f2b">("${occurrence.givenValue}")</span>`;
        occurrencesList.appendChild(occurrenceElement);
    });
    occurrencesElement.appendChild(occurrencesList);
    document.getElementById('bigsheet').appendChild(occurrencesElement);
};
