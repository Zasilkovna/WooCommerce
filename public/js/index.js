(()=>{"use strict";const e=window.wp.blocks,t=window.wc.blocksCheckout,i=JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"packeta/packeta-widget","version":"0.1.0","title":"Packeta Widget","category":"woocommerce","parent":["woocommerce/checkout-shipping-methods-block"],"attributes":{"lock":{"type":"object","default":{"remove":true,"move":true}}},"icon":"cart","description":"Packeta Widget Block","textdomain":"packeta-widget","viewScript":"file:./index.js"}'),n=window.React,o=window.wp.blockEditor,c=({children:e,buttonLabel:t,logoSrc:i,logoAlt:o,info:c,onClick:a,loading:r,placeholderText:l})=>(0,n.createElement)("div",{className:"packetery-widget-button-wrapper"},r&&(0,n.createElement)("div",{className:"packeta-widget-loading"},l),!r&&(0,n.createElement)("div",{className:"form-row packeta-widget blocks"},(0,n.createElement)("div",{className:"packetery-widget-button-row packeta-widget-button"},(0,n.createElement)("img",{className:"packetery-widget-button-logo",src:i,alt:o}),(0,n.createElement)("a",{onClick:a,className:"button alt components-button wc-block-components-button wp-element-button contained"},t)),e,c&&(0,n.createElement)("p",{className:"packeta-widget-info"},c))),a=window.wc.wcSettings,r=window.wc.blocksComponents,l=window.wp.i18n;(0,e.registerBlockType)(i,{title:(0,l.__)("title","packeta"),description:(0,l.__)("description","packeta"),edit:()=>{const e=(0,o.useBlockProps)();return(0,n.createElement)("div",{...e},(0,n.createElement)(c,{buttonLabel:"Choose pickup point",logoSrc:"data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDI1LjEuMCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IlZyc3R2YV8xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB4PSIwcHgiIHk9IjBweCIKCSB2aWV3Qm94PSIwIDAgMzcgNDAiIHN0eWxlPSJmaWxsOiNhN2FhYWQiIHhtbDpzcGFjZT0icHJlc2VydmUiPgo8c3R5bGUgdHlwZT0idGV4dC9jc3MiPgoJLnN0MHtmaWxsLXJ1bGU6ZXZlbm9kZDtjbGlwLXJ1bGU6ZXZlbm9kZDtmaWxsOiAjYTdhYWFkO30KPC9zdHlsZT4KPHBhdGggY2xhc3M9InN0MCIgZD0iTTE5LjQsMTYuMWwtMC45LDAuNGwtMC45LTAuNGwtMTMtNi41bDYuMi0yLjRsMTMuNCw2LjVMMTkuNCwxNi4xeiBNMzIuNSw5LjZsLTQuNywyLjNsLTEzLjUtNmw0LjItMS42CglMMzIuNSw5LjZ6Ii8+CjxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik0xOSwwbDE3LjIsNi42bC0yLjQsMS45bC0xNS4yLTZMMy4yLDguNkwwLjgsNi42TDE4LDBMMTksMEwxOSwweiBNMzQuMSw5LjFsMi44LTEuMWwtMi4xLDE3LjZsLTAuNCwwLjgKCUwxOS40LDQwbC0wLjUtMy4xbDEzLjQtMTJMMzQuMSw5LjF6IE0yLjUsMjYuNWwtMC40LTAuOEwwLDguMWwyLjgsMS4xbDEuOSwxNS43bDEzLjQsMTJMMTcuNiw0MEwyLjUsMjYuNXoiLz4KPHBhdGggY2xhc3M9InN0MCIgZD0iTTI4LjIsMTIuNGw0LjMtMi43bC0xLjcsMTQuMkwxOC42LDM1bDAuNi0xN2w1LjQtMy4zTDI0LjMsMjNsMy4zLTIuM0wyOC4yLDEyLjR6Ii8+CjxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik0xNy43LDE3LjlsMC42LDE3bC0xMi4yLTExTDQuNCw5LjhMMTcuNywxNy45eiIvPgo8L3N2Zz4K",logoAlt:"Packeta",info:"Pickup Point Name"}))}}),(0,t.registerCheckoutBlock)({metadata:i,component:({cart:e})=>{const{shippingRates:t}=e,i=(0,a.getSetting)("packeta-widget_data"),{carrierConfig:o,translations:l,logo:s,widgetAutoOpen:u,adminAjaxUrl:w}=i,p=((e,t)=>{if(!e||0===e.length)return null;const{shipping_rates:i}=e[0];return i&&0!==i.length&&i.find((({rate_id:e,selected:i})=>{if(!i)return!1;const n=e.split(":").pop(),o=t[n];if(!o)return!1;const{is_pickup_points:c}=o;return o&&c}))||null})(t,o),[d,g]=(e=>{let[t,i]=(0,n.useState)(null),[o,c]=(0,n.useState)(!1);return[t,o]=((e,t,i,o,c)=>((0,n.useEffect)((()=>{const n=function(){let e=document.querySelector("#shipping-country input");return e||(e=document.querySelector("#billing-country input")),e?e.value:null}();null!==n&&(null===t||null===t.countryName?i((e=>({...e,countryName:n}))):t.countryName!==n&&(o||(c(!0),fetch(e,{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:new URLSearchParams({action:"translate_country_name",countryName:n})}).then((e=>e.json())).then((e=>{null!==e&&i((t=>({...t,country:e})))})).catch((e=>{console.error("Error:",e)})).finally((()=>{c(!1)}))),i((e=>({...e,countryName:n})))))})),[t,o]))(e,t,i,o,c),(0,n.useEffect)((()=>{o||null!==t||(c(!0),fetch(e,{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:new URLSearchParams({action:"get_settings"})}).then((e=>e.json())).then((e=>{const{weight:t,isAgeVerificationRequired:n}=e;i((e=>({...e,weight:t,isAgeVerificationRequired:n})))})).catch((e=>{console.error("Error:",e),i(!1)})).finally((()=>{c(!1)})))}),[t,e,o]),[t,o]})(w),[m,k]=((e,t,i)=>{const{carrierConfig:o,country:c,language:a,packeteryApiKey:r,appIdentity:l,nonce:s,saveSelectedPickupPointUrl:u,pickupPointAttrs:w}=t,[p,d]=(0,n.useState)(null);return[(0,n.useCallback)((()=>{const t=e.rate_id.split(":").pop();let n=0;i&&i.weight&&(n=i.weight);let p=c;i&&i.country&&(p=i.country);let g={country:p,language:a,appIdentity:l,weight:n};o[t].carriers&&(g.carriers=o[t].carriers),o[t].vendors&&(g.vendors=o[t].vendors),i&&i.isAgeVerificationRequired&&(g.livePickupPoint=!0);let m={};Packeta.Widget.pick(r,(e=>{if(!e)return;d({pickupPoint:e}),function(e,t,i){for(let n in t){if(!t.hasOwnProperty(n))continue;const{name:o,widgetResultField:c,isWidgetResultField:a}=t[n];if(!1===a)continue;let r=i[c||n];m[e]=m[e]||{},m[e][o]=r}}(t,w,e);let i=m[t];i.packetery_rate_id=t,fetch(u,{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded","X-WP-Nonce":s},body:new URLSearchParams(i)}).then((e=>{if(!e.ok)throw new Error("HTTP error "+e.status)})).catch((e=>{console.error("Failed to save pickup point data:",e)}))}),g)}),[e,i]),p]})(p,i,d);(0,n.useEffect)((()=>{p&&d&&!k&&u&&m()}),[p,u,m]);const{choosePickupPoint:M,packeta:L}=l;return p?(0,n.createElement)(c,{onClick:m,buttonLabel:M,logoSrc:s,logoAlt:L,info:k&&k.pickupPoint&&k.pickupPoint.name,loading:g,placeholderText:l.placeholderText},(0,n.createElement)(r.ValidatedTextInput,{value:k&&k.pickupPoint&&k.pickupPoint.name,required:!0,errorMessage:function(e){return e&&e.pickupPoint?null:l.pickupPointNotChosen}(k)})):null}})})();