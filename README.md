# Koqoi.com - Sitio Web Corporativo

Sitio web oficial de Koqoi, empresa de tecnología especializada en soluciones para el sector salud.

## Estructura del Proyecto

```
Koqoi.com/
├── index.html              # Página principal
├── assets/
│   ├── css/
│   │   └── styles.css      # Estilos principales
│   ├── js/
│   │   └── main.js         # JavaScript principal
│   └── img/
│       └── logo.jpeg       # Logo de la empresa (colocar aquí)
├── pages/
│   ├── privacidad.html     # Política de privacidad
│   └── terminos.html       # Términos de uso
└── README.md
```

## 📍 Ubicación del Logo

**Coloca el logo de tu empresa en:**
```
assets/img/logo.jpeg
```

### Especificaciones recomendadas para el logo:
- Formato: JPEG
- Dimensiones: 200px de ancho x 60px de alto (aproximadamente)
- Peso: Máximo 100KB para optimizar la carga

## Características del Sitio

✅ Diseño moderno y profesional  
✅ Totalmente responsive (móvil, tablet, desktop)  
✅ Navegación suave entre secciones  
✅ Formulario de contacto funcional  
✅ Animaciones y efectos interactivos  
✅ Optimizado para SEO  
✅ Colores corporativos personalizables  

## Secciones Incluidas

1. **Inicio (Hero)** - Presentación principal con call-to-action
2. **Servicios** - 6 tarjetas de servicios principales
3. **Soluciones** - Detalle de productos/apps
4. **Nosotros** - Información de la empresa
5. **Contacto** - Formulario y datos de contacto

## Cómo Usar

### Servidor Local (XAMPP)

1. El sitio ya está en `c:\xampp\htdocs\Koqoi.com`
2. Inicia Apache desde el panel de XAMPP
3. Abre tu navegador y visita: `http://localhost/Koqoi.com`

### Personalización

#### Cambiar Colores
Edita las variables en `assets/css/styles.css`:
```css
:root {
    --primary-color: #2563eb;
    --secondary-color: #7c3aed;
    --accent-color: #06b6d4;
}
```

#### Modificar Contenido
- Textos: Edita `index.html`
- Información de contacto: Líneas 280-320 de `index.html`
- Servicios: Líneas 65-135 de `index.html`

#### Configurar Formulario
Para que el formulario envíe emails reales, necesitarás implementar un backend. El código actual muestra una alerta. Modifica en `assets/js/main.js` (líneas 60-90).

## Tecnologías Utilizadas

- HTML5
- CSS3 (con Variables CSS y Grid/Flexbox)
- JavaScript Vanilla (ES6+)
- Google Fonts (Inter)
- SVG para iconos

## Navegadores Compatibles

✅ Chrome (últimas 2 versiones)  
✅ Firefox (últimas 2 versiones)  
✅ Safari (últimas 2 versiones)  
✅ Edge (últimas 2 versiones)  

## Próximos Pasos Recomendados

1. ✅ Agregar el logo en `assets/img/logo.png`
2. Actualizar datos de contacto (teléfono, dirección)
3. Configurar backend para el formulario
4. Agregar imágenes reales en las secciones
5. Integrar Google Analytics (opcional)
6. Configurar dominio y hosting para producción

## Soporte

Para preguntas o soporte técnico:
- Email: info@koqoi.com
- Web: https://koqoi.com

---

**Desarrollado con ❤️ para Koqoi**
