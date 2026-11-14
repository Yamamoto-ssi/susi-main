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
function openModal(type){
 if(!backdrop||!form)return;
 const login=type==="login";
 form.dataset.authMode=login?"login":"signup";
 modalTitle.textContent=login?"Welcome back":"Create your account";
 modalDescription.textContent=login?"Log in to continue to your HomeBid account.":"Join HomeBid and discover your next home.";
 const nameLabel=form.querySelector("label:first-child");
 if(nameLabel)nameLabel.style.display=login?"none":"grid";
 const submit=form.querySelector(".form-submit");if(submit)submit.textContent=login?"Log In":"Create Account";
 formMessage.textContent="";backdrop.classList.add("open");backdrop.setAttribute("aria-hidden","false");document.body.style.overflow="hidden";
}
function closeModal(){
 if(!backdrop)return;
 backdrop.classList.remove("open");backdrop.setAttribute("aria-hidden","true");document.body.style.overflow="";
}
document.querySelectorAll("[data-modal]").forEach(button=>button.addEventListener("click",()=>openModal(button.dataset.modal)));
document.getElementById("modalClose")?.addEventListener("click",closeModal);
backdrop?.addEventListener("click",event=>{if(event.target===backdrop)closeModal();});
document.addEventListener("keydown",event=>{if(event.key==="Escape")closeModal();});
form?.addEventListener("submit",async event=>{
 event.preventDefault();
 const submit=form.querySelector(".form-submit");if(submit)submit.disabled=true;
 formMessage.textContent="Please wait…";
 const data=new FormData(form);data.set("action",form.dataset.authMode||"signup");
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
function tick(){if(!timer)return;const d=Math.floor(seconds/86400),h=Math.floor(seconds%86400/3600),m=Math.floor(seconds%3600/60);timer.textContent=`${String(d).padStart(2,"0")}d : ${String(h).padStart(2,"0")}h : ${String(m).padStart(2,"0")}m`;if(seconds>0)seconds--;}
tick();if(timer)setInterval(tick,60000);
