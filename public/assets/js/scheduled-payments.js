(() => {
 const root=document.querySelector('.scheduled-payments'); if(!root) return;
 const search=root.querySelector('#scheduled-search'),frequency=root.querySelector('#scheduled-frequency'),status=root.querySelector('#scheduled-status');
 const normalize=value=>value.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
 const filter=()=>{
  const term=normalize(search.value); let count=0;
  root.querySelectorAll('[data-payment]').forEach(card=>{
   const visible=(!term || normalize(card.dataset.search+' '+card.querySelector('h3').textContent+' '+card.querySelector('dl').textContent).includes(term)) && (!frequency.value || card.dataset.frequency===frequency.value) && (!status.value || card.dataset.status===status.value);
   card.hidden=!visible; if(visible) count++;
  });
  root.querySelector('#scheduled-count').textContent=count;
  root.querySelector('#scheduled-empty').hidden=count>0;
 };
 search.addEventListener('input',filter);frequency.addEventListener('change',filter);status.addEventListener('change',filter);
 root.querySelector('a[href="#nuevo-pago"]').addEventListener('click',()=>{root.querySelector('#scheduled-new').open=true;});
 root.querySelectorAll('.scheduled-editor,.scheduled-generate').forEach(form=>form.addEventListener('submit',()=>{
  const button=form.querySelector('button[type=submit]'); if(button) button.disabled=true;
 }));
})();