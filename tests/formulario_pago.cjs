const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../public/gastos/ingresar.php'), 'utf8');
const script = source.slice(source.indexOf('function habilitar(id) {'), source.indexOf('</script>', source.indexOf('function habilitar(id) {')));
const controls = [];
const button = {};
// Un input name="id" oculta la propiedad form.id en un formulario real.
const form = {id:{nodeName:'INPUT'}, dataset:{}, getAttribute:name=>name==='id'?'form722':null, querySelector:()=>button};
const cells = new Map();
for (const name of ['monto','fp','cf','tp','nt']) cells.set(name+'722', {dataset:{amount:'45.20'}, replaceChildren(control){this.control=control;}, querySelector(){return this.control;}});
const document = {
 getElementById:id=>id==='form722'?form:cells.get(id),
 createElement:tag=>{const control={tag,attributes:{},options:[],setAttribute(name,value){this.attributes[name]=value;},add(option){this.options.push(option);},focus(){}};controls.push(control);return control;}
};
const context={document,tarjetasPagoOpciones:[{nombre_tarjeta:'BANK OF AMERICA',medio_id:'cuenta:3'}],Option:function(text,value){this.text=text;this.value=value;}};
vm.createContext(context);vm.runInContext(script,context);context.habilitar(722);
assert.equal(controls.length,5);
for(const control of controls) assert.equal(control.attributes.form,'form722','Cada campo debe apuntar al formulario del pago, sin usar form.id');
const select=controls.find(c=>c.name==='medio_pago_id');
assert.equal(select.options[1].value,'cuenta:3');assert.equal(select.required,true);
assert.equal(controls.find(c=>c.name==='amount').value,'45.20');
assert.equal(button.type,'submit');
context.habilitar(722);assert.equal(controls.length,5,'No duplica campos al pulsar otra vez');
console.log('OK: campos asociados al formulario con input name=id; BANK OF AMERICA envía cuenta:3; monto editable y sin campos duplicados.');
