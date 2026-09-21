'use strict';
(() => {
 let widget, stream, capture, captureUrl, generation = 0;
 const find = name => widget?.querySelector(`[data-camera-${name}]`);
 function stopStream() { stream?.getTracks().forEach(track => track.stop()); stream = null; }
 function clearCapture() { capture = null; if (captureUrl) URL.revokeObjectURL(captureUrl); captureUrl = null; }
 function close() {
  generation++; stopStream(); clearCapture();
  if (widget) { find('panel').hidden = true; find('video').srcObject = null; find('open').disabled = false; }
 }
 function message(text) { if (widget?.isConnected) find('message').textContent = text; }
 function errorMessage(error) {
  return ({NotAllowedError:'Camera access was blocked. Allow camera access in your browser, then try again.',NotFoundError:'No camera was found. Connect your webcam and try again.',NotReadableError:'The camera is unavailable or in use. Close other camera apps and try again.',OverconstrainedError:'That camera is no longer available. Reconnect it or select another camera.'})[error.name] || 'Unable to open the camera. Please try again or upload a photo.';
 }
 function rememberedCamera() { try { const saved=localStorage.getItem('visitor-desk-camera'); return saved?.startsWith('device:') ? saved : ''; } catch { return ''; } }
 function rememberCamera(choice) { try { localStorage.setItem('visitor-desk-camera',choice); } catch {} }
 async function refreshCameras(choice) {
  const target=widget, ticket=generation;
  const devices=await navigator.mediaDevices.enumerateDevices();
  if(target!==widget || ticket!==generation || !widget?.isConnected)return;
  const select=find('device'), selected=choice || select.value || rememberedCamera();
  select.replaceChildren();
  devices.filter(d=>d.kind==='videoinput' && d.deviceId).forEach((d,i)=>select.add(new Option(d.label || `Connected camera ${i+1}`,`device:${d.deviceId}`)));
  select.value=[...select.options].some(o=>o.value===selected)?selected:'';

 }
 async function start(choice=rememberedCamera()) {
  const ticket = ++generation; stopStream(); clearCapture();
  find('panel').hidden = false; find('video').hidden = false; find('capture').hidden = true;
  find('snap').hidden = false; find('snap').disabled = true; find('retake').hidden = true; find('use').hidden = true;
  find('device').disabled = true; find('open').disabled = true;
  message('Opening camera...');
  try {
   if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) throw new Error('secure-context');
   await refreshCameras(choice);
   if(ticket!==generation)return;
   const selection=choice.startsWith('device:')?{deviceId:{exact:choice.slice(7)}}:{};
   const incoming = await navigator.mediaDevices.getUserMedia({audio:false,video:{...selection,width:{ideal:1280},height:{ideal:960}}});
   if (ticket !== generation || !widget?.isConnected) { incoming.getTracks().forEach(t=>t.stop()); return; }
   stream = incoming; const video = find('video'); video.srcObject = incoming; await video.play();
   if (ticket !== generation) return;
   const track=incoming.getVideoTracks()[0];
   choice=track.getSettings().deviceId ? `device:${track.getSettings().deviceId}` : choice;
   await refreshCameras(choice);
   if (ticket !== generation) return;
   find('device').disabled = false; find('snap').disabled = false;
   rememberCamera(choice);
   message(`Using ${track.label || 'selected camera'}. For a USB webcam, select its device name from the camera list.`);
   incoming.getVideoTracks()[0].addEventListener('ended',()=>{if(ticket===generation){find('snap').disabled=true;find('open').disabled=false;message('Camera disconnected. Reconnect it and open the camera again.');}});
  } catch(error) {
   if (ticket !== generation) return;
   stopStream(); find('open').disabled = false; find('device').disabled = false;
   message(error.message==='secure-context'?'Live capture needs HTTPS or localhost. Open this app using HTTPS, or use the upload option.':errorMessage(error));
  }
 }
 document.addEventListener('click',async event=>{
  const button = event.target.closest('[data-camera-open],[data-camera-close],[data-camera-snap],[data-camera-retake],[data-camera-use],[data-camera-refresh]'); if(!button)return;
  const nextWidget = button.closest('[data-camera-widget]');
  if(widget!==nextWidget){close();widget=nextWidget;}
  if(button.hasAttribute('data-camera-refresh')){try{await refreshCameras();message('Camera list refreshed. Select your external webcam by its device name.');}catch{message('Unable to refresh cameras. Check the webcam connection and browser permission.');}return;}
  if(button.hasAttribute('data-camera-open')){await start();return;}
  if(button.hasAttribute('data-camera-close')){close();message('Camera closed. Your selected upload is unchanged.');return;}
  if(button.hasAttribute('data-camera-retake')){await start(find('device').value);return;}
  if(button.hasAttribute('data-camera-snap')){
   const video=find('video'); if(!video.videoWidth || !stream)return;
   const ticket=generation;button.disabled=true;
   const canvas=document.createElement('canvas'),scale=Math.min(1,1600/video.videoWidth);
   canvas.width=Math.round(video.videoWidth*scale);canvas.height=Math.round(video.videoHeight*scale);
   canvas.getContext('2d').drawImage(video,0,0,canvas.width,canvas.height);
   canvas.toBlob(blob=>{
    if(ticket!==generation)return;
    if(!blob){button.disabled=false;message('Could not capture the photo. Try again.');return;}
    capture=blob;captureUrl=URL.createObjectURL(blob);find('capture').src=captureUrl;find('capture').hidden=false;video.hidden=true;
    stopStream();find('snap').hidden=true;find('retake').hidden=false;find('use').hidden=false;
    message('Review your photo. Choose Use photo to attach it to this visit.');
   },'image/jpeg',0.9);return;
  }
  if(button.hasAttribute('data-camera-use')&&capture){
   try {
    const transfer=new DataTransfer();transfer.items.add(new File([capture],`visitor-${Date.now()}.jpg`,{type:'image/jpeg'}));
    const input=widget.querySelector('[name="photo"]');input.files=transfer.files;close();input.dispatchEvent(new Event('change',{bubbles:true}));
    message('Photo attached. Save the visitor form to store it.');
   }catch(error){message('Could not attach this photo. Please use the upload option.');}
  }
 });
 document.addEventListener('change',event=>{
  if(event.target.matches('[data-camera-device]'))start(event.target.value);
  if(event.target.matches('[name="photo"]')&&widget?.contains(event.target)){close();}
 });
 navigator.mediaDevices?.addEventListener?.('devicechange',()=>{if(widget?.isConnected && !find('panel').hidden)refreshCameras().catch(()=>{});});
 document.addEventListener('visitor:navigate',close);
 window.addEventListener('pagehide',close);
 document.addEventListener('submit',event=>{if(!event.defaultPrevented && widget && event.target.contains(widget))close();});
})();
