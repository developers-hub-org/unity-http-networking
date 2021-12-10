namespace DevelopersHub
{

    using System.Collections;
    using System.Collections.Generic;
    using System.Text;
    using UnityEngine;
    using UnityEngine.Networking;
    using LitJson;

    public class Network : MonoBehaviour
    {

        [SerializeField] private Settings _settings = null;
        public Settings settings { get { return _settings; } set { _settings = value; } }
        private static Network instance = null;
        public static Network Instance { get { if (instance == null) { instance = FindObjectOfType<Network>(); } return instance; } }
        public delegate void ServerDataCallback(int requestID, string message, JsonData data);
        public event ServerDataCallback OnRequestResponded;

        public void SendRequest(int requestID, Dictionary<string, object> data)
        {
            StartCoroutine(Request(requestID, data));
        }

        private IEnumerator Request(int requestID, Dictionary<string, object> data)
        {

            #region Create Essential Data
            string iv = Encryption.GenerateVI(16);
            string validation = Encryption.GenerateVI(Random.Range(10, 30));
            StringBuilder sb = new StringBuilder();
            JsonWriter writer = new JsonWriter(sb);
            #endregion

            #region Write Json Data
            writer.WriteObjectStart();
            if(data != null)
            {
                foreach (KeyValuePair<string, object> item in data)
                {
                    if (item.Value is string)
                    {
                        writer.WritePropertyName(item.Key); writer.Write((string)item.Value);
                    }
                    else if (item.Value is int)
                    {
                        writer.WritePropertyName(item.Key); writer.Write((int)item.Value);
                    }
                    else if (item.Value is float)
                    {
                        writer.WritePropertyName(item.Key); writer.Write((float)item.Value);
                    }
                    else if (item.Value is double)
                    {
                        writer.WritePropertyName(item.Key); writer.Write((double)item.Value);
                    }
                    else if (item.Value is bool)
                    {
                        writer.WritePropertyName(item.Key); writer.Write((bool)item.Value);
                    }
                    else if (item.Value is decimal)
                    {
                        writer.WritePropertyName(item.Key); writer.Write((decimal)item.Value);
                    }
                    else if (item.Value is long)
                    {
                        writer.WritePropertyName(item.Key); writer.Write((long)item.Value);
                    }
                    else if (item.Value is ulong)
                    {
                        writer.WritePropertyName(item.Key); writer.Write((ulong)item.Value);
                    }
                    else
                    {
                        Debug.LogError("This object is not valid: " + item.Key);
                    }
                }
            }
            writer.WritePropertyName("request"); writer.Write(requestID);
            writer.WritePropertyName("validation"); writer.Write(validation);
            writer.WritePropertyName("version"); writer.Write(Application.version);
            writer.WriteObjectEnd();
            #endregion

            #region Initialize Form
            WWWForm form = new WWWForm();
            form.AddField("hash0", iv);
            form.AddField("hash1", Encryption.EncryptAES(sb.ToString(), iv, _settings.EncryptionKey));
            form.AddField("hash2", Encryption.EncryptMD5(validation, _settings.MD5Key));
            #endregion

            #region Web Request Method
            string result = "";
            using (UnityWebRequest request = UnityWebRequest.Post(_settings.ApiAddress, form))
            {
                yield return request.SendWebRequest();
                if (request.isNetworkError || request.isHttpError)
                {
                    Debug.LogError(request.error);
                }
                else
                {
                    result = request.downloadHandler.text;
                }
            }
            #endregion

            #region WWW Method
            /*
            float connectionTimeout = 20f;
            WWW Data = new WWW(Settings.Instance.ApiAddress, form);
            float timer = 0;
            bool failed = false;
            while (!Data.isDone)
            {
                if (timer >= connectionTimeout) { failed = true; break; }
                timer += Time.deltaTime;
                yield return null;
            }
            if (failed || !string.IsNullOrEmpty(Data.error))
            {
                Debug.LogError(Data.error);
                Data.Dispose();
                OnRequestResponded?.Invoke(requestID, "", null);
                yield break;
            }
            yield return Data;
            string result = Data.text;
            */
            #endregion

            #region Call Event
            try
            {
                JsonData mainJason = JsonMapper.ToObject(result);
                JsonData json = JsonMapper.ToObject(Encryption.DecryptAES(mainJason["hash0"].ToString(), mainJason["hash1"].ToString(), _settings.EncryptionKey));
                string message = json["message"].ToString();
                if (Encryption.EncryptMD5(message, _settings.MD5Key) == mainJason["hash2"].ToString())
                {
                    OnRequestResponded?.Invoke(requestID, message, (message == "SUCCESSFUL" && json.ContainsKey("data")) ? json["data"] : null);
                }
                else
                {
                    Debug.LogError("ERROR_VALIDATION_CLIENT");
                    OnRequestResponded?.Invoke(requestID, "", null);
                }
            }
            catch (System.Exception ex)
            {
                Debug.LogError(ex.Message);
                OnRequestResponded?.Invoke(requestID, string.IsNullOrEmpty(result) ? "" : "ERROR_UNKNOWN", null);
            }
            #endregion

        }

    }
}
