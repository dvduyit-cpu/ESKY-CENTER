document.addEventListener('DOMContentLoaded', () => {
    const csvCell = value => `"${String(value ?? '').replace(/\s+/g, ' ').trim().replaceAll('"', '""')}"`;
    const downloadCsv = (table, rows) => {
        const headers = [...table.tHead?.rows?.[0]?.cells ?? []]
            .filter(cell => !cell.matches('[data-selection-column]'))
            .map(cell => csvCell(cell.innerText));
        const body = rows.map(row => [...row.cells]
            .filter(cell => !cell.matches('[data-selection-column]'))
            .map(cell => csvCell(cell.innerText)).join(','));
        const blob = new Blob(['\uFEFF' + [headers.join(','), ...body].join('\r\n')], {type: 'text/csv;charset=utf-8'});
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `du-lieu-da-chon-${new Date().toISOString().slice(0, 10)}.csv`;
        link.click();
        URL.revokeObjectURL(link.href);
    };

    document.querySelectorAll('.table-modern').forEach((table, tableIndex) => {
        if (!document.querySelector('.app-pagination')) return;
        const rows = [...table.tBodies].flatMap(body => [...body.rows])
            .filter(row => !row.querySelector('.empty-state'));
        if (!rows.length) return;

        let boxes = rows.map(row => row.querySelector('[data-bulk-item]')).filter(Boolean);
        const generated = boxes.length === 0;
        if (!generated) {
            const formId = boxes[0]?.getAttribute('form');
            const bulkForm = formId ? document.getElementById(formId) : boxes[0]?.closest('form');
            if (bulkForm && !bulkForm.querySelector('[data-list-export]')) {
                const exportButton = document.createElement('button');
                exportButton.type = 'button'; exportButton.className = 'btn btn-sm btn-outline-success';
                exportButton.dataset.listExport = ''; exportButton.disabled = true;
                exportButton.innerHTML = '<i class="bi bi-file-earmark-spreadsheet me-1"></i>Xuất dòng đã chọn';
                bulkForm.append(exportButton);
                const refreshExport = () => exportButton.disabled = !boxes.some(box => box.checked);
                boxes.forEach(box => box.addEventListener('change', refreshExport));
                bulkForm.querySelector('[data-bulk-all]')?.addEventListener('change', () => setTimeout(refreshExport));
                exportButton.addEventListener('click', () => downloadCsv(table, boxes.filter(box => box.checked).map(box => box.closest('tr'))));
                refreshExport();
            }
            return;
        }
        if (generated) {
            const head = table.tHead?.rows?.[0];
            if (head) {
                const th = document.createElement('th');
                th.dataset.selectionColumn = '';
                th.className = 'text-center';
                th.style.width = '42px';
                head.prepend(th);
            }
            boxes = rows.map((row, index) => {
                const td = document.createElement('td');
                td.dataset.selectionColumn = '';
                td.className = 'text-center';
                const box = document.createElement('input');
                box.type = 'checkbox';
                box.className = 'form-check-input';
                box.dataset.listSelect = `${tableIndex}-${index}`;
                td.append(box);
                row.prepend(td);
                return box;
            });
        }

        const toolbar = document.createElement('div');
        toolbar.className = 'list-selection-toolbar d-flex flex-wrap align-items-center gap-2 p-3 border-bottom';
        toolbar.innerHTML = `<label class="d-flex align-items-center gap-2 mb-0"><input type="checkbox" class="form-check-input" data-list-select-all> Chọn tất cả trang này</label><span class="badge-soft badge-info" data-list-count>0 đã chọn</span><button type="button" class="btn btn-sm btn-outline-success" data-list-export disabled><i class="bi bi-file-earmark-spreadsheet me-1"></i>Xuất dòng đã chọn</button>`;
        const container = table.closest('.table-responsive') ?? table;
        container.before(toolbar);
        const all = toolbar.querySelector('[data-list-select-all]');
        const count = toolbar.querySelector('[data-list-count]');
        const exportButton = toolbar.querySelector('[data-list-export]');

        const selectedRows = () => boxes.filter(box => box.checked).map(box => box.closest('tr'));
        const deleteForms = row => [...row.querySelectorAll('form')].filter(form => form.querySelector('input[name="_method"][value="DELETE"]'));
        const canDelete = generated && rows.some(row => deleteForms(row).length);
        let deleteButton = null;
        if (canDelete) {
            deleteButton = document.createElement('button');
            deleteButton.type = 'button';
            deleteButton.className = 'btn btn-sm btn-outline-danger';
            deleteButton.innerHTML = '<i class="bi bi-trash me-1"></i>Xóa dòng đã chọn';
            deleteButton.disabled = true;
            toolbar.append(deleteButton);
        }
        const refresh = () => {
            const selected = selectedRows();
            count.textContent = `${selected.length} đã chọn`;
            exportButton.disabled = selected.length === 0;
            all.checked = selected.length === boxes.length;
            all.indeterminate = selected.length > 0 && selected.length < boxes.length;
            if (deleteButton) deleteButton.disabled = !selected.length || selected.some(row => !deleteForms(row).length);
        };
        boxes.forEach(box => box.addEventListener('change', refresh));
        all.addEventListener('change', () => { boxes.forEach(box => box.checked = all.checked); refresh(); });
        exportButton.addEventListener('click', () => downloadCsv(table, selectedRows()));
        deleteButton?.addEventListener('click', async () => {
            const selected = selectedRows();
            if (!selected.length || !confirm(`Xóa ${selected.length} dòng đã chọn?`)) return;
            deleteButton.disabled = true;
            for (const row of selected) {
                const form = deleteForms(row)[0];
                const response = await fetch(form.action, {method: 'POST', body: new FormData(form), headers: {'X-Requested-With': 'XMLHttpRequest'}});
                if (!response.ok) { alert('Có dòng không thể xóa do phân quyền hoặc đang được sử dụng.'); break; }
            }
            window.location.reload();
        });
        refresh();
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const cards = [...document.querySelectorAll('[data-list-card]')];
    if (!cards.length) return;
    const existingBoxes = cards.map(card => card.querySelector('[data-bulk-item]')).filter(Boolean);
    if (existingBoxes.length) {
        const formId = existingBoxes[0].getAttribute('form');
        const bulkForm = formId ? document.getElementById(formId) : null;
        if (bulkForm && !bulkForm.querySelector('[data-list-export]')) {
            const button = document.createElement('button');
            button.type = 'button'; button.className = 'btn btn-sm btn-outline-success'; button.dataset.listExport = ''; button.disabled = true;
            button.innerHTML = '<i class="bi bi-file-earmark-spreadsheet me-1"></i>Xuất dòng đã chọn'; bulkForm.append(button);
            const refresh = () => button.disabled = !existingBoxes.some(box => box.checked);
            existingBoxes.forEach(box => box.addEventListener('change', refresh)); bulkForm.querySelector('[data-bulk-all]')?.addEventListener('change', () => setTimeout(refresh));
            button.addEventListener('click', () => { const rows=cards.filter((card,index)=>existingBoxes[index]?.checked).map(card=>'"'+card.innerText.replace(/\s+/g,' ').trim().replaceAll('"','""')+'"'); const blob=new Blob(['\uFEFFNội dung\r\n'+rows.join('\r\n')],{type:'text/csv;charset=utf-8'}); const link=document.createElement('a'); link.href=URL.createObjectURL(blob); link.download='du-lieu-da-chon.csv'; link.click(); URL.revokeObjectURL(link.href); });
            refresh();
        }
        return;
    }
    const toolbar = document.createElement('div');
    toolbar.dataset.cardSelectionToolbar = '';
    toolbar.className = 'list-selection-toolbar d-flex flex-wrap align-items-center gap-2 p-3 mb-3';
    toolbar.innerHTML = '<label class="d-flex align-items-center gap-2 mb-0"><input type="checkbox" class="form-check-input" data-card-all> Chọn tất cả trang này</label><span class="badge-soft badge-info" data-card-count>0 đã chọn</span><button class="btn btn-sm btn-outline-success" type="button" data-card-export disabled><i class="bi bi-file-earmark-spreadsheet me-1"></i>Xuất dòng đã chọn</button>';
    cards[0].closest('.row')?.before(toolbar);
    const boxes = cards.map(card => {
        const box = document.createElement('input');
        box.type = 'checkbox'; box.className = 'form-check-input position-absolute';
        box.style.cssText = 'top:12px;left:12px;z-index:2';
        card.style.position = 'relative'; card.style.paddingLeft = '28px'; card.prepend(box);
        return box;
    });
    const all = toolbar.querySelector('[data-card-all]'), count = toolbar.querySelector('[data-card-count]'), exportButton = toolbar.querySelector('[data-card-export]');
    const deletable = cards.some(card => card.querySelector('[data-card-delete]'));
    let deleteButton = null;
    if (deletable) { deleteButton = document.createElement('button'); deleteButton.type = 'button'; deleteButton.className = 'btn btn-sm btn-outline-danger'; deleteButton.innerHTML = '<i class="bi bi-trash me-1"></i>Xóa đã chọn'; toolbar.append(deleteButton); }
    const selected = () => cards.filter((card, index) => boxes[index].checked);
    const refresh = () => { const list=selected(); cards.forEach((card,index)=>card.classList.toggle('is-selected',boxes[index].checked)); count.textContent=`${list.length} đã chọn`; exportButton.disabled=!list.length; all.checked=list.length===cards.length; all.indeterminate=list.length>0&&list.length<cards.length; if(deleteButton)deleteButton.disabled=!list.length||list.some(card=>!card.querySelector('[data-card-delete]')); };
    boxes.forEach(box => box.addEventListener('change', refresh)); all.addEventListener('change',()=>{boxes.forEach(box=>box.checked=all.checked);refresh()});
    exportButton.addEventListener('click',()=>{const rows=selected().map(card=>'"'+card.innerText.replace(/\s+/g,' ').trim().replaceAll('"','""')+'"');const blob=new Blob(['\uFEFFNội dung\r\n'+rows.join('\r\n')],{type:'text/csv;charset=utf-8'});const link=document.createElement('a');link.href=URL.createObjectURL(blob);link.download='cong-viec-da-chon.csv';link.click();URL.revokeObjectURL(link.href)});
    deleteButton?.addEventListener('click',async()=>{const list=selected();if(!confirm(`Xóa ${list.length} công việc đã chọn?`))return;for(const card of list){const form=card.querySelector('[data-card-delete]');const response=await fetch(form.action,{method:'POST',body:new FormData(form),headers:{'X-Requested-With':'XMLHttpRequest'}});if(!response.ok){alert('Có công việc không thể xóa.');break}}location.reload()});
    refresh();
});
