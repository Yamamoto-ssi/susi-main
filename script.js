const menuToggle=document.getElementById("menuToggle");
const mainNav=document.getElementById("mainNav");
if(menuToggle&&mainNav){
 menuToggle.addEventListener("click",()=>{const open=mainNav.classList.toggle("open");menuToggle.setAttribute("aria-expanded",String(open));});
 mainNav.querySelectorAll("a").forEach(link=>link.addEventListener("click",()=>{mainNav.classList.remove("open");menuToggle.setAttribute("aria-expanded","false");}));
}
const backdrop=document.getElementById("modalBackdrop");
const modalTitle=document.getElementById("modalTitle");
const modalDescription=document.getElementById("modalDescription");
const form=document.getElementById("accountForm");
const formMessage=document.getElementById("formMessage");
const nameField=document.getElementById("nameField");
const nameInput=form?.elements.namedItem("name");
const passwordInput=form?.elements.namedItem("password");
const authSwitch=document.getElementById("authSwitch");
const googleSignInSection=document.getElementById("googleSignInSection");
const googleSignInButton=document.getElementById("googleSignInButton");
const googleSignInNote=document.getElementById("googleSignInNote");
let googleButtonRendered=false;

function renderGoogleSignInButton(){
 if(!googleSignInSection||googleSignInSection.hidden)return;
 if(!window.HOMEBID_GOOGLE_CLIENT_ID){
  if(googleSignInNote)googleSignInNote.textContent="Google Sign-In needs a configured OAuth Client ID.";
  return;
 }
 if(!window.google?.accounts?.id||!googleSignInButton)return;
 googleSignInButton.innerHTML="";
 window.google.accounts.id.initialize({
  client_id:window.HOMEBID_GOOGLE_CLIENT_ID,
  callback:handleGoogleCredential
 });
 window.google.accounts.id.renderButton(googleSignInButton,{
  type:"standard",theme:"outline",size:"large",shape:"rectangular",
  text:"continue_with",logo_alignment:"left",width:360
 });
 googleButtonRendered=true;
 if(googleSignInNote)googleSignInNote.textContent="";
}

async function handleGoogleCredential(response){
 if(!response?.credential)return;
 if(formMessage)formMessage.textContent="Signing in with Google…";
 try{
  const data=new FormData();
  data.set("credential",response.credential);
  const resultResponse=await fetch("google-login.php",{
   method:"POST",body:data,headers:{"Accept":"application/json"}
  });
  const result=await resultResponse.json();
  if(!resultResponse.ok||!result.ok)throw new Error(result.message||"Google sign-in failed.");
  if(formMessage)formMessage.textContent=result.message||"Google sign-in successful.";
  if(result.redirect)window.location.href=result.redirect;
 }catch(error){
  if(formMessage)formMessage.textContent=error.message||"Google sign-in failed. Please try again.";
 }
}

function openModal(type){
 if(!backdrop||!form)return;
 const login=type==="login";
 form.dataset.authMode=login?"login":"signup";
 modalTitle.textContent=login?"Welcome back":"Create your account";
 modalDescription.textContent=login?"Log in to continue to your HomeBid account.":"Join HomeBid and discover your next home.";
 if(nameField){nameField.hidden=login;nameInput.required=!login;}
 if(googleSignInSection){googleSignInSection.hidden=!login;if(login)renderGoogleSignInButton();}
 if(googleSignInNote&&!window.HOMEBID_GOOGLE_CLIENT_ID)googleSignInNote.textContent="Google Sign-In needs a configured OAuth Client ID.";
 if(passwordInput){passwordInput.autocomplete=login?"current-password":"new-password";passwordInput.minLength=login?0:8;}
 const submit=form.querySelector(".form-submit");
 if(submit)submit.textContent=login?"Log In":"Create Account";
 if(authSwitch)authSwitch.innerHTML=login?'New to HomeBid? <button type="button" data-modal="signup">Create an account</button>':'Already have an account? <button type="button" data-modal="login">Log in</button>';
 formMessage.textContent="";
 form.reset();
 backdrop.classList.add("open");
 backdrop.setAttribute("aria-hidden","false");
 document.body.style.overflow="hidden";
 const first=login?form.elements.namedItem("email"):nameInput;
 first?.focus();
}
function closeModal(){
 if(!backdrop)return;
 backdrop.classList.remove("open");
 backdrop.setAttribute("aria-hidden","true");
 document.body.style.overflow="";
}
document.querySelectorAll("[data-modal]").forEach(button=>button.addEventListener("click",()=>openModal(button.dataset.modal)));
authSwitch?.addEventListener("click",event=>{
 const button=event.target.closest("[data-modal]");
 if(button)openModal(button.dataset.modal);
});
document.getElementById("modalClose")?.addEventListener("click",closeModal);
backdrop?.addEventListener("click",event=>{if(event.target===backdrop)closeModal();});
document.addEventListener("keydown",event=>{if(event.key==="Escape")closeModal();});

form?.addEventListener("submit",async event=>{
 event.preventDefault();
 const mode=form.dataset.authMode||"signup";
 const email=form.elements.namedItem("email").value.trim();
 const password=passwordInput.value;
 if(mode==="signup"&&!nameInput.value.trim()){formMessage.textContent="Please enter your full name.";nameInput.focus();return;}
 if(!email||!form.elements.namedItem("email").checkValidity()){formMessage.textContent="Please enter a valid email address.";form.elements.namedItem("email").focus();return;}
 if(!password||(mode==="signup"&&password.length<8)){formMessage.textContent=mode==="signup"?"Your password must be at least 8 characters.":"Please enter your password.";passwordInput.focus();return;}
 const submit=form.querySelector(".form-submit");
 if(submit)submit.disabled=true;
 formMessage.textContent="Please wait…";
 const data=new FormData(form);
 data.set("action",mode);
 try{
  const response=await fetch("login-agent.php",{method:"POST",body:data,headers:{"Accept":"application/json"}});
  const result=await response.json();
  if(!response.ok||!result.ok)throw new Error(result.message||"Unable to complete your request.");
  formMessage.textContent=result.message||"Success.";
  if(result.redirect)window.location.href=result.redirect;
 }catch(error){formMessage.textContent=error.message||"A network error occurred. Please try again.";}
 finally{if(submit)submit.disabled=false;}
});

let toastTimer;
function showToast(message){
 const toast=document.getElementById("toast");if(!toast)return;
 toast.textContent=message;toast.classList.add("show");clearTimeout(toastTimer);
 toastTimer=setTimeout(()=>toast.classList.remove("show"),2800);
}
document.querySelectorAll(".view-property").forEach(button=>button.addEventListener("click",()=>{
 if(button.dataset.property==="Cebu Skyline Townhouse")window.location.href="property-view.php";
 else showToast(button.dataset.property+" — its detail page can be added next.");
}));
let seconds=3*86400+5*3600+18*60;
const timer=document.getElementById("countdown");
function tick(){
 if(!timer)return;
 const d=Math.floor(seconds/86400),h=Math.floor(seconds%86400/3600),m=Math.floor(seconds%3600/60);
 timer.textContent=`${String(d).padStart(2,"0")}d : ${String(h).padStart(2,"0")}h : ${String(m).padStart(2,"0")}m`;
 if(seconds>0)seconds--;
}
tick();
if(timer)setInterval(tick,60000);

window.addEventListener("load",()=>{if(form?.dataset.authMode==="login")renderGoogleSignInButton();});
