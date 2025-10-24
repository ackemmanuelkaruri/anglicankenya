// modules/members/js/members.js
document.addEventListener('DOMContentLoaded',function(){
  // general AJAX form submit
  function ajaxForm(form, onSuccess, onFail){
    form.addEventListener('submit', function(e){
      e.preventDefault();
      let data = new FormData(form);
      fetch(form.action, {method:'POST', body: data, credentials:'same-origin'})
        .then(r=>r.json())
        .then(json => {
          if (json.success) onSuccess(json);
          else onFail(json);
        })
        .catch(err => onFail({message:'Network error'}));
    });
  }

  const personal = document.getElementById('personalForm');
  if (personal) {
    ajaxForm(personal, (j)=>{document.getElementById('personalMsg').innerText = j.message}, (j)=>{document.getElementById('personalMsg').innerText = j.message});
  }
  const contact = document.getElementById('contactForm');
  if (contact) {
    ajaxForm(contact, (j)=>{document.getElementById('contactMsg').innerText = j.message}, (j)=>{document.getElementById('contactMsg').innerText = j.message});
  }
  const church = document.getElementById('churchForm');
  if (church) {
    ajaxForm(church, (j)=>{document.getElementById('churchMsg').innerText = j.message}, (j)=>{document.getElementById('churchMsg').innerText = j.message});
  }

  // Photo upload form uses same page action (not XHR file upload fallback)
  const upload = document.getElementById('uploadPhotoForm');
  if (upload) {
    upload.addEventListener('submit', function(e){
      e.preventDefault();
      let data = new FormData(upload);
      fetch(upload.action, {method:'POST', body: data, credentials:'same-origin'})
        .then(r=>r.json()).then(j=>{
          document.getElementById('profileMessages').innerText = j.message || (j.success?'Uploaded':'Failed');
        }).catch(()=>{document.getElementById('profileMessages').innerText='Upload failed';});
    });
  }
});
