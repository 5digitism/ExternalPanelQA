const requestForm=document.getElementById('requestForm');
const requestSuccess=document.getElementById('requestSuccess');
const resetForm=document.getElementById('resetForm');
const resetSuccess=document.getElementById('resetSuccess');
const resetMsg=document.getElementById('resetMsg');

function getQueryParam(name){
  return new URLSearchParams(window.location.search).get(name);
}

const token=getQueryParam('token');
if(token){
  requestForm.classList.add('hidden');
  requestSuccess.classList.add('hidden');
  resetForm.classList.remove('hidden');
}

requestForm.addEventListener('submit',e=>{
  e.preventDefault();
  const email=document.getElementById('email').value.trim();
  if(!email)return alert('Please enter your email');

  requestForm.classList.add('hidden');
  requestSuccess.classList.remove('hidden');
});

resetForm.addEventListener('submit',e=>{
  e.preventDefault();
  const p1=document.getElementById('newPassword').value;
  const p2=document.getElementById('confirmPassword').value;
  resetMsg.textContent='';
  resetMsg.className='';

  if(p1.length<8){
    resetMsg.className='error';
    resetMsg.textContent='Password must be at least 8 characters.';
    return;
  }
  if(p1!==p2){
    resetMsg.className='error';
    resetMsg.textContent='Passwords do not match.';
    return;
  }

  resetForm.classList.add('hidden');
  resetSuccess.classList.remove('hidden');
});

document.getElementById('backToLogin').onclick=()=>location.href='loginpage.html';

document.getElementById('backtoLogin').onclick=()=>location.href='loginpage.html';

document.getElementById('cancelReset').onclick=()=>{
  const url=new URL(location);
  url.searchParams.delete('token');
  history.replaceState({},'',url);
  resetForm.classList.add('hidden');
  requestForm.classList.remove('hidden');
};

document.getElementById('goLogin').onclick=e=>{
  e.preventDefault();
  location.href='/login';
};
