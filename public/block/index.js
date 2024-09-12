(()=>{"use strict";const e=window.wp.blocks,t=window.wc.blocksCheckout,o=JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"packeta/packeta-widget","version":"0.1.0","title":"Packeta Widget","category":"woocommerce","parent":["woocommerce/checkout-shipping-methods-block"],"attributes":{"lock":{"type":"object","default":{"remove":true,"move":true}}},"icon":"cart","description":"Packeta Widget Block","textdomain":"packeta-widget","viewScript":"file:./index.js"}'),n=window.React,i=window.wp.blockEditor,a=({children:e,buttonLabel:t,logoSrc:o,logoAlt:i,info:a,onClick:c,loading:r,placeholderText:s})=>(0,n.createElement)("div",{className:"packetery-widget-button-wrapper"},r&&(0,n.createElement)("div",{className:"packeta-widget-loading"},s),!r&&(0,n.createElement)("div",{className:"form-row packeta-widget blocks"},(0,n.createElement)("div",{className:"packetery-widget-button-row packeta-widget-button"},(0,n.createElement)("img",{className:"packetery-widget-button-logo",src:o,alt:i}),(0,n.createElement)("a",{onClick:c,className:"button alt components-button wc-block-components-button wp-element-button contained"},t)),e,a&&(0,n.createElement)("p",{className:"packeta-widget-info"},a))),c=window.wp.data,r=window.wc.wcSettings,s=window.wc.blocksComponents,{PAYMENT_STORE_KEY:l}=window.wc.wcBlocksData,d=window.wp.i18n,{extensionCartUpdate:p}=wc.blocksCheckout,u=function(e,t){let o=null;if(t)o=t.value;else{const e=document.querySelector('input[name="radio-control-wc-payment-method-options"]:checked');null!==e&&(o=e.value)}let n=null;if(e)n=e.shippingRateId;else{let e=document.querySelectorAll('.wc-block-components-shipping-rates-control input[type="radio"]');for(let t=0;t<e.length;t++)if(e[t].checked){n=e[t].value;break}}let i={};n&&(i.shipping_method=n),o&&(i.payment_method=o),"{}"!==JSON.stringify(i)&&p({namespace:"packetery-js-hooks",data:i})};(0,e.registerBlockType)(o,{title:(0,d.__)("title","packeta"),description:(0,d.__)("description","packeta"),edit:()=>{const e=(0,i.useBlockProps)();return(0,n.createElement)("div",{...e},(0,n.createElement)(a,{buttonLabel:"Choose pickup point",logoSrc:"data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDI1LjEuMCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IlZyc3R2YV8xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB4PSIwcHgiIHk9IjBweCIKCSB2aWV3Qm94PSIwIDAgMzcgNDAiIHN0eWxlPSJmaWxsOiNhN2FhYWQiIHhtbDpzcGFjZT0icHJlc2VydmUiPgo8c3R5bGUgdHlwZT0idGV4dC9jc3MiPgoJLnN0MHtmaWxsLXJ1bGU6ZXZlbm9kZDtjbGlwLXJ1bGU6ZXZlbm9kZDtmaWxsOiAjYTdhYWFkO30KPC9zdHlsZT4KPHBhdGggY2xhc3M9InN0MCIgZD0iTTE5LjQsMTYuMWwtMC45LDAuNGwtMC45LTAuNGwtMTMtNi41bDYuMi0yLjRsMTMuNCw2LjVMMTkuNCwxNi4xeiBNMzIuNSw5LjZsLTQuNywyLjNsLTEzLjUtNmw0LjItMS42CglMMzIuNSw5LjZ6Ii8+CjxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik0xOSwwbDE3LjIsNi42bC0yLjQsMS45bC0xNS4yLTZMMy4yLDguNkwwLjgsNi42TDE4LDBMMTksMEwxOSwweiBNMzQuMSw5LjFsMi44LTEuMWwtMi4xLDE3LjZsLTAuNCwwLjgKCUwxOS40LDQwbC0wLjUtMy4xbDEzLjQtMTJMMzQuMSw5LjF6IE0yLjUsMjYuNWwtMC40LTAuOEwwLDguMWwyLjgsMS4xbDEuOSwxNS43bDEzLjQsMTJMMTcuNiw0MEwyLjUsMjYuNXoiLz4KPHBhdGggY2xhc3M9InN0MCIgZD0iTTI4LjIsMTIuNGw0LjMtMi43bC0xLjcsMTQuMkwxOC42LDM1bDAuNi0xN2w1LjQtMy4zTDI0LjMsMjNsMy4zLTIuM0wyOC4yLDEyLjR6Ii8+CjxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik0xNy43LDE3LjlsMC42LDE3bC0xMi4yLTExTDQuNCw5LjhMMTcuNywxNy45eiIvPgo8L3N2Zz4K",logoAlt:"Packeta",info:"Pickup Point Name"}))}}),(0,t.registerCheckoutBlock)({metadata:o,component:({cart:e})=>{const[t,o]=(0,n.useState)(null),{shippingRates:i,shippingAddress:d,cartItemsWeight:p}=e,u=(0,c.useSelect)((e=>e(l)),[]),w=(0,r.getSetting)("packeta-widget_data"),{carrierConfig:k,translations:g,logo:h,widgetAutoOpen:m,adminAjaxUrl:y}=w,M=((e,t)=>{if(!e||0===e.length)return null;const{shipping_rates:o}=e[0];if(!o||0===o.length)return null;const n=e=>o.find((({rate_id:o,selected:n})=>{if(!n)return!1;const i=o.split(":").pop(),a=t[i];return!!a&&e(a)}));return{packetaPickupPointShippingRate:n((e=>{const{is_pickup_points:t}=e;return e&&t}))||null,packetaHomeDeliveryShippingRate:n((e=>{const{is_pickup_points:t}=e;return e&&!t}))||null,chosenShippingRate:o.find((({selected:e})=>e))||null}})(i,k),{packetaPickupPointShippingRate:f=null,packetaHomeDeliveryShippingRate:L=null,chosenShippingRate:b=null}=M||{},[I,C,N]=(e=>{let[t,o]=(0,n.useState)(null),[i,a]=(0,n.useState)(!1);return(0,n.useEffect)((()=>{i||null!==t||(a(!0),fetch(e,{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:new URLSearchParams({action:"get_settings"})}).then((e=>e.json())).then((e=>{const{isAgeVerificationRequired:t}=e;o((e=>({...e,isAgeVerificationRequired:t})))})).catch((e=>{console.error("Error:",e),o(!1)})).finally((()=>{a(!1)})))}),[t,e,i]),[t,o,i]})(y);(0,n.useEffect)((()=>{if(!I)return;const e=u.getActivePaymentMethod(),t=b?.rate_id||null;let o=!1,n=!1;(!I.shippingSaved&&t||!I.paymentSaved&&""!==e)&&(t&&(o=!0),""!==e&&(n=!0),wp.hooks.doAction("packetery_save_shipping_and_payment_methods",t,e),C({...I,shippingSaved:o,paymentSaved:n}))}),[u,b,I,C,wp]),(0,n.useEffect)((()=>{if(!I)return;const e=d.country.toLowerCase();I.lastCountry?I.lastCountry!==e&&(t&&o(null),C({...I,lastCountry:e})):C({...I,lastCountry:e})}),[I,C,t,o,d]);const S=((e,t,o,i,a,c)=>{const{carrierConfig:r,language:s,packeteryApiKey:l,appIdentity:d,nonce:p,saveSelectedPickupPointUrl:u,pickupPointAttrs:w}=t;return(0,n.useCallback)((()=>{const t=e.rate_id.split(":").pop();let n=+(c/1e3).toFixed(2),k={language:s,appIdentity:d,weight:n};k.country=a.country.toLowerCase(),r[t].carriers&&(k.carriers=r[t].carriers),r[t].vendors&&(k.vendors=r[t].vendors),o&&o.isAgeVerificationRequired&&(k.livePickupPoint=!0);let g={};Packeta.Widget.pick(l,(e=>{if(!e)return;i({pickupPoint:e}),function(e,t,o){for(let n in t){if(!t.hasOwnProperty(n))continue;const{name:i,widgetResultField:a,isWidgetResultField:c}=t[n];if(!1===c)continue;let r=o[a||n];g[e]=g[e]||{},g[e][i]=r}}(t,w,e);let o=g[t];o.packetery_rate_id=t,fetch(u,{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded","X-WP-Nonce":p},body:new URLSearchParams(o)}).then((e=>{if(!e.ok)throw new Error("HTTP error "+e.status)})).catch((e=>{console.error("Failed to save pickup point data:",e)}))}),k)}),[e,o])})(f,w,I,o,d,p),P=((e,t,o,i,a)=>{const{carrierConfig:c,language:r,packeteryApiKey:s,appIdentity:l,nonce:d,saveValidatedAddressUrl:p,homeDeliveryAttrs:u}=t;return(0,n.useCallback)((()=>{const o=e.rate_id.split(":").pop();let n={language:r,appIdentity:l,layout:"hd"};n.country=a.country.toLowerCase(),n.street=a.address_1,n.city=a.city,n.postcode=a.postcode,n.carrierId=c[o].id;let w={};Packeta.Widget.pick(s,(e=>{if(!e||!e.address)return;if(e.address.country!==n.country)return void i({deliveryAddressError:t.translations.invalidAddressCountrySelected});i({deliveryAddressInfo:e.address.name}),function(e,t,o){for(let n in t){if(!t.hasOwnProperty(n))continue;const{name:i,widgetResultField:a,isWidgetResultField:c}=t[n];if(!1===c)continue;let r=o[a||n];w[e]=w[e]||{},w[e][i]=r}}(o,u,e.address);let a=w[o];a.packetery_rate_id=o,a.packetery_address_isValidated=1,fetch(p,{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded","X-WP-Nonce":d},body:new URLSearchParams(a)}).then((e=>{if(!e.ok)throw new Error("HTTP error "+e.status)})).catch((e=>{console.error("Failed to save pickup point data:",e)}))}),n)}),[e,o])})(L,w,I,o,d);(0,n.useEffect)((()=>{f&&I&&!t&&m&&S()}),[f,m,S]);const v=function(e,t){return"optional"===t||e&&e.deliveryAddressInfo?null:e&&e.deliveryAddressError?e.deliveryAddressError:g.addressIsNotValidatedAndRequiredByCarrier},{choosePickupPoint:j,chooseAddress:T,packeta:E}=g;if(f)return(0,n.createElement)(a,{onClick:S,buttonLabel:j,logoSrc:h,logoAlt:E,info:t&&t.pickupPoint&&t.pickupPoint.name,loading:N,placeholderText:g.placeholderText},(0,n.createElement)(s.ValidatedTextInput,{value:t&&t.pickupPoint?t.pickupPoint.name:"",required:!0,errorMessage:function(e){return e&&e.pickupPoint?null:g.pickupPointNotChosen}(t)}));if(L){const e=k[L.rate_id.split(":").pop()].address_validation||"none";return"none"===e?null:("optional"!==e||t&&t.deliveryAddressInfo||o({deliveryAddressInfo:g.addressIsNotValidated}),(0,n.createElement)(a,{onClick:P,buttonLabel:T,logoSrc:h,logoAlt:E,info:t&&t.deliveryAddressInfo,loading:N,placeholderText:g.placeholderText},(0,n.createElement)(s.ValidatedTextInput,{value:t&&t.deliveryAddressInfo?t.deliveryAddressInfo:"",required:!0,errorMessage:v(t,e)})))}return null}});const{extensionCartUpdate:w}=wc.blocksCheckout;wp.hooks.addAction("experimental__woocommerce_blocks-checkout-set-selected-shipping-rate","packetery-js-hooks",(function(e){u(e,null)})),wp.hooks.addAction("experimental__woocommerce_blocks-checkout-set-active-payment-method","packetery-js-hooks",(function(e){u(null,e)})),wp.hooks.addAction("experimental__woocommerce_blocks-checkout-render-checkout-form","packetery-js-hooks",(function(e){w({namespace:"packetery-js-hooks",data:{shipping_method:"n/a",payment_method:"n/a"}})})),wp.hooks.addAction("packetery_save_shipping_and_payment_methods","packetery-js-hooks",(function(e,t){u(e?{shippingRateId:e}:null,""!==t?{value:t}:null)}))})();