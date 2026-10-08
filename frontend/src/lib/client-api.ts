/**
 * Browser-side API helpers. XHR is used for uploads so the UI can show real
 * upload progress (fetch does not expose upload progress in all browsers).
 */

export type LeadResponse = {
  ok?: boolean;
  trackingCode?: string;
  errors?: Record<string, string>;
  error?: string;
};

export function postLead(body: FormData, onProgress?: (fraction: number) => void): Promise<{ status: number; data: LeadResponse }> {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "/api/leads");
    xhr.responseType = "text";
    xhr.timeout = 120_000;
    xhr.upload.onprogress = (e) => {
      if (e.lengthComputable && onProgress) onProgress(e.loaded / e.total);
    };
    xhr.onload = () => {
      let data: LeadResponse = {};
      try {
        data = JSON.parse(xhr.responseText) as LeadResponse;
      } catch {
        data = {};
      }
      resolve({ status: xhr.status, data });
    };
    xhr.onerror = () => reject(new Error("network"));
    xhr.ontimeout = () => reject(new Error("timeout"));
    xhr.send(body);
  });
}
