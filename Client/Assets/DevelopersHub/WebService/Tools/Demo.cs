namespace DevelopersHub
{
    using LitJson;
    using System.Collections;
    using System.Collections.Generic;
    using UnityEngine;
    using UnityEngine.UI;

    public class Demo : MonoBehaviour
    {

        public Button btnRequest0 = null;
        public Button btnRequest1 = null;
        public Button btnRequest2 = null;
        public InputField inpR1User = null;
        public InputField inpR1Pass = null;
        public InputField inpR2User = null;
        public Text txtResponse = null;

        private void Start()
        {
            Network.Instance.OnRequestResponded += Instance_OnRequestResponded;
            btnRequest0.onClick.AddListener(SendRequest_0);
            btnRequest1.onClick.AddListener(SendRequest_1);
            btnRequest2.onClick.AddListener(SendRequest_2);
        }

        /*
        private void OnEnable()
        {
            Network.Instance.OnRequestResponded += Instance_OnRequestResponded;
        }

        private void OnDisable()
        {
            Network.Instance.OnRequestResponded -= Instance_OnRequestResponded;
        }
        */

        public void SendRequest_0()
        {
            btnRequest0.interactable = false;
            Network.Instance.SendRequest(0, null);
        }

        public void SendRequest_1()
        {
            btnRequest1.interactable = false;
            Dictionary<string, object> data = new Dictionary<string, object>();
            data.Add("username", inpR1User.text);
            data.Add("password", inpR1Pass.text);
            Network.Instance.SendRequest(1, data);
        }

        public void SendRequest_2()
        {
            btnRequest2.interactable = false;
            Dictionary<string, object> data = new Dictionary<string, object>();
            data.Add("username", inpR2User.text);
            Network.Instance.SendRequest(2, data);
        }

        private void Instance_OnRequestResponded(int requestID, string message, JsonData data)
        {
            if (!string.IsNullOrEmpty(message))
            {
                switch (requestID)
                {
                    case 0:
                        if (!data.IsObject && data.ToString() == "TABLE_CREATED")
                        {
                            txtResponse.text = "Table created/already existed successfully.";
                        }
                        break;
                    case 1:
                        if (!data.IsObject && data.ToString() == "ACCOUNT_CREATED")
                        {
                            txtResponse.text = "Account has been created successfully.";
                        }
                        else if (!data.IsObject && data.ToString() == "USERNAME_EXISTS")
                        {
                            txtResponse.text = "This user already exists.";
                        }
                        break;
                    case 2:
                        if (!data.IsObject && data.ToString() == "USERNAME_NOT_EXIST")
                        {
                            txtResponse.text = "Account do not exist.";
                        }
                        else
                        {
                            if (data.Count > 0)
                            {
                                string username = data["username"].ToString();
                                int id = int.Parse(data["id"].ToString());
                                int score = int.Parse(data["score"].ToString());
                                txtResponse.text = "Account " + username + " has score=" + score.ToString() + " and id=" + id.ToString();
                            }
                        }
                        break;
                    default:

                        break;
                }
            }
            else
            {
                txtResponse.text = "Failed to connect the server.";
            }
            btnRequest0.interactable = true;
            btnRequest1.interactable = true;
            btnRequest2.interactable = true;
        }

    }
}