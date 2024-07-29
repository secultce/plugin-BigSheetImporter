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

            // if(response.statusText === 'Invalid')
            if(!response.ok)
                return console.error(response);

            const data = await response.json();
        } catch (e) {
            console.error(e);
        }
    });
    document.getElementById('avaliacoes').parentElement.appendChild(importSheetElement);
});
