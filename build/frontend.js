!function(e){const t=e("#send-invitations"),n=e("#send-invitations-list");let o;$newConnectionSearch=e("#new-connection-search"),$newConnectionSearch.length&&($newConnectionSearch.autocomplete({minLength:2,source:ajaxurl+"?action=openlab_connection_group_search",select:function(e,t){if(document.getElementById("connection-invitation-group-"+t.item.groupId))return!1;const o=wp.template("openlab-connection-invitation")(t.item);return n.append(o),c(),!1}}).autocomplete("instance")._renderItem=function(t,n){return e("<li>").append("<div><strong>"+n.groupName+"</strong><br>"+n.groupUrl+"</div>").appendTo(t)}),n.on("click",".remove-connection-invitation",(function(e){return e.target.closest(".group-item").remove(),c(),!1}));const c=()=>{n.children().length>0?t.show():t.hide()};document.querySelectorAll(".accordion").forEach((e=>{const t=e.querySelector(".accordion-toggle"),n=e.querySelector(".accordion-content");t.addEventListener("click",(()=>{const e="true"===t.getAttribute("aria-expanded")||!1;t.setAttribute("aria-expanded",!e),n.style.display=e?"none":"block"}))})),document.querySelectorAll(".disconnect-button").forEach((e=>{const t=e.getAttribute("aria-label"),n=e.innerHTML;e.addEventListener("mouseenter",(()=>{e.textContent=t})),e.addEventListener("mouseleave",(()=>{e.textContent=n}))})),e(".connection-tax-term-selector").select2({width:"80%"});const i=t=>{const n=document.getElementById("connection-settings-"+t),o=document.getElementById("connection-setting-"+t+"-post").checked,c=document.getElementById("connection-setting-"+t+"-comment").checked,i=e("#connection-setting-"+t+"-category-terms").val(),s=e("#connection-setting-"+t+"-tag-terms").val(),r=e("#connection-settings-"+t+"-nonce").val(),a={connectionId:t,postToggle:o,commentToggle:c,selectedPostCategories:i,selectedPostTags:s,groupId:n.closest(".connections-settings").dataset.groupId,nonce:r};e.post({url:ajaxurl+"?action=openlab_connections_save_connection_settings",data:a})};document.querySelectorAll('.connection-settings input[type="checkbox"]').forEach((e=>{e.addEventListener("change",(()=>{clearTimeout(o),o=setTimeout((()=>{i(e.closest(".connection-settings").dataset.connectionId)}),500)}))})),e(".connection-settings select").on("select2:select",(e=>{clearTimeout(o),o=setTimeout((()=>{i(e.target.closest(".connection-settings").dataset.connectionId)}),500)}))}(jQuery);