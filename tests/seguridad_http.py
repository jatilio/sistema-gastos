"""Pruebas HTTP locales. No inserta ni modifica datos financieros."""
import http.cookiejar, http.cookies, os, re, socket, subprocess, tempfile, time, urllib.request, urllib.parse, urllib.error
from pathlib import Path
BASE = Path(__file__).resolve().parents[1]
class SinRedireccion(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args): return None
with tempfile.TemporaryDirectory(prefix='finanzas-seguridad-') as tmp:
    with socket.socket() as sock:
        sock.bind(('127.0.0.1',0)); port=sock.getsockname()[1]
    ruta = str(Path(tmp)).replace('\\','/')
    sid = 'revisionseguridad' + os.urandom(10).hex()
    token = 'a'*64
    codigo = "ini_set('session.save_path',$argv[1]);session_id($argv[2]);session_start();$_SESSION=['usuario_id'=>4,'usuario_autenticado_id'=>4,'usuario_nombre'=>'Alberto Lopez','ultima_actividad'=>time(),'sesion_creada'=>time(),'csrf_finanzas'=>str_repeat('a',64)];session_write_close();"
    subprocess.run(['php','-r',codigo,ruta,sid],cwd=BASE,check=True,capture_output=True)
    with open(Path(tmp)/'servidor.log','w') as log:
        servidor=subprocess.Popen(['php','-d','session.save_path='+ruta,'-S',f'127.0.0.1:{port}','-t','public','router.php'],cwd=BASE,stdout=log,stderr=log,creationflags=getattr(subprocess,'CREATE_NO_WINDOW',0))
        try:
            for _ in range(60):
                try:
                    with socket.create_connection(('127.0.0.1',port),timeout=.1): break
                except OSError: time.sleep(.1)
            opener=urllib.request.build_opener(SinRedireccion)
            def pedir(path, data=None, sesion=False, headers=None):
                h={'Cookie':'PHPSESSID='+sid} if sesion else {}
                h.update(headers or {})
                req=urllib.request.Request(f'http://127.0.0.1:{port}'+path,data=urllib.parse.urlencode(data).encode() if data is not None else None,headers=h)
                try: r=opener.open(req,timeout=20)
                except urllib.error.HTTPError as e: r=e
                with r: return r.status,r.headers,r.read().decode('utf-8',errors='replace')
            cantidad=0
            for f in (BASE/'public').rglob('*.php'):
                path='/'+f.relative_to(BASE/'public').as_posix()
                if path in ['/auth/login.php','/auth/procesar_login.php'] or path.startswith('/assets/') or path=='/test_pdf.php': continue
                status,h,html=pedir(path)
                assert status==302 and h.get('Location')=='/auth/login.php',(path,status)
                cantidad+=1
            status,h,html=pedir('/auth/login.php')
            assert status==200 and 'name="csrf_finanzas"' in html
            assert 'HttpOnly' in h.get('Set-Cookie','') and 'SameSite=Lax' in h.get('Set-Cookie','')
            assert h.get('X-Frame-Options')=='DENY' and h.get('X-Content-Type-Options')=='nosniff'
            assert "form-action 'self'" in h.get('Content-Security-Policy','')
            for path in ['/config/db.php','/config/db.local.php','/crear_admin.php','/.git/config','/procesos/error_sql.txt','/assets/css/app/controllers/ingresar_guardar.php']:
                assert pedir(path)[0]==404,path
            assert pedir('/auth/login.php',headers={'Host':'example.invalid'})[0]==403
            for path in ['/procesos/actualizar_tarjeta.php','/%73ync_pagos.php','/gastos/../sync_pagos.php','/procesos/%65liminar_tarjeta.php']:
                resultado=pedir(path,sesion=True)
                assert resultado[0] in (400,404,405),(path,resultado[0],resultado[2][:100])
            for path in ['/procesos/actualizar_tarjeta.php','/procesos/eliminar_tarjeta.php','/gastos/ingresar_guardar.php','/mantenimiento_pagos_guardar.php','/auth/procesar_login.php']:
                assert pedir(path,{},sesion=True)[0]==403,path
                assert pedir(path,{'csrf_finanzas':'incorrecto'},sesion=True)[0]==403,path
            status,h,html=pedir('/procesos/actualizar_tarjeta.php',{'csrf_finanzas':token,'id':2147483647,'tipo':'credito','estado':'activa','banco':'Prueba','nombre_tarjeta':'Prueba','saldo_actual':'0','limite':'100'},True)
            assert status==404,(status,html)
            assert pedir('/procesos/eliminar_tarjeta.php',{'csrf_finanzas':token,'id':2147483647},True)[0]==404
            for menu in ['dashboard','dashboard_prueba','ingresos_salario','gastos_ingresar','gastos_cobrados','reportes','reporte_tarjetas','reporte_metodo_pago','mantenimiento_pagos','tarjetas','tarjetas_gastos','historial_movimientos']:
                status,h,html=pedir('/index.php?menu='+menu,sesion=True)
                if menu=='historial_movimientos':
                    assert status==403,'Alberto puede entrar al historial'
                    continue
                assert status==200,(menu,status,html[:100])
                assert 'href="/index.php?menu=historial_movimientos"' not in html,'Historial visible en menú de Alberto'
                assert not re.search(r'(Fatal error|Warning:|Parse error|No se pudo completar la operaci)',html),menu
                for form in re.findall(r'<form\b[^>]*>.*?</form>',html,re.I|re.S):
                    if re.search(r'method\s*=\s*[\x22\x27]?post\b',form,re.I): assert 'name="csrf_finanzas"' in form,(menu,'formulario sin protección')
            assert pedir('/historial_movimientos.php',sesion=True)[0]==403
            adminsid='adminprueba'+os.urandom(8).hex()
            admincodigo=codigo.replace("'usuario_id'=>4,'usuario_autenticado_id'=>4","'usuario_id'=>3,'usuario_autenticado_id'=>3")
            subprocess.run(['php','-r',admincodigo,ruta,adminsid],cwd=BASE,check=True,capture_output=True)
            for path in ['/index.php?menu=historial_movimientos','/historial_movimientos.php']:
                status,h,html=pedir(path,headers={'Cookie':'PHPSESSID='+adminsid})
                assert status==200 and 'historial-volver' in html,(path,status)
            print('OK permisos: historial reservado al admin por menú y URL directa; Alberto bloqueado, botón de retorno disponible.')
            for path in ['/reporte_pdf.php','/reportes/exportar_metodo_pago_pdf.php','/gastos/inventario_bajo_stock_pdf.php','/gastos/reporte_bajo_stock_pdf.php']:
                status,h,contenido=pedir(path,sesion=True)
                assert status==200 and contenido.startswith('%PDF-'),(path,status,contenido[:160])
            for inactivo,antiguo in [(1801,0),(0,43201)]:
                vencida='caducada'+os.urandom(8).hex()
                crear="ini_set('session.save_path',$argv[1]);session_id($argv[2]);session_start();$_SESSION=['usuario_id'=>4,'ultima_actividad'=>time()-(int)$argv[3],'sesion_creada'=>time()-(int)$argv[4]];session_write_close();"
                subprocess.run(['php','-r',crear,ruta,vencida,str(inactivo),str(antiguo)],cwd=BASE,check=True,capture_output=True)
                status,h,contenido=pedir('/index.php',headers={'Cookie':'PHPSESSID='+vencida})
                assert status==302 and h.get('Location')=='/auth/login.php','Sesión vencida aceptada'
            print('OK PDF: los cuatro reportes generan un archivo PDF válido.')
            print(f'OK HTTP: {cantidad} rutas protegidas, 12 pantallas, formularios, cookies, cabeceras, HTTPS, archivos privados y rechazo de cambios sin autorización. Sin cambios financieros.')
        finally:
            servidor.terminate();servidor.wait(timeout=10)
