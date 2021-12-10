namespace DevelopersHub
{

    using System.IO;
    using UnityEngine;

    #if UNITY_EDITOR
    using UnityEditor;
    #endif

    /// <summary>
    /// Collection of connection-relevant settings.
    /// </summary>
    public class Settings : ScriptableObject
    {

        [Tooltip("Link to your php file on the server.")]
        [SerializeField] private string apiAddress = "https://demo.com/dh_unity_server_public/api.php";
        public string ApiAddress { get { return apiAddress; } }

        [Tooltip("AES encryption password must be 32 characters. This password must be the same as the one in the server. This will be used to secure the data.")]
        [SerializeField] private string encryptionKey = "abcdefghijklmnopqrstuvwxyz012345";
        public string EncryptionKey { get { return encryptionKey; } }

        [Tooltip("MD5 encryption password can be any number of characters. This password must be the same as the one in the server.. This will be used to validate the data.")]
        [SerializeField] private string mD5Key = "abcdefg";
        public string MD5Key { get { return mD5Key; } }

        #if UNITY_EDITOR
        [MenuItem("Window/Developers Hub/Web Service/Settings")] public static void CreateSettings()
        {
            string[] guids = AssetDatabase.FindAssets("t:" + typeof(Settings).Name);
            if(guids.Length > 0)
            {
                string path = AssetDatabase.GUIDToAssetPath(guids[0]);
                EditorUtility.FocusProjectWindow();
                Object obj = AssetDatabase.LoadAssetAtPath<Object>(path);
                Selection.activeObject = obj;
            }
            else
            {
                string path = Application.dataPath + "/DevelopersHub/WebService";
                if (!Directory.Exists(path))
                {
                    Directory.CreateDirectory(path);
                }
                Settings asset = ScriptableObject.CreateInstance<Settings>();
                AssetDatabase.CreateAsset(asset, "Assets/DevelopersHub/WebService/Settings.asset");
                AssetDatabase.SaveAssets();
                EditorUtility.FocusProjectWindow();
                Selection.activeObject = asset;
            }
        }

        [MenuItem("Window/Developers Hub/Web Service/Network")] public static void CreateNetwork()
        {
            Network[] networks = FindObjectsOfType<Network>();
            Settings settings = FindInstance();
            if (networks.Length == 0)
            {
                Network network = new GameObject("WebService").AddComponent<Network>();
                if (settings)
                {
                    network.settings = settings;
                }
                EditorUtility.FocusProjectWindow();
                Selection.activeObject = network;
            }
            else
            {
                if (settings)
                {
                    networks[0].settings = settings;
                }
                EditorUtility.FocusProjectWindow();
                Selection.activeObject = networks[networks.Length - 1];
                if (networks.Length > 1)
                {
                    for (int i = networks.Length - 2; i >= 0; i--)
                    {
                        DestroyImmediate(networks[i]);
                    }
                }
            }
        }

        private static Settings FindInstance()
        {
            string[] guids = AssetDatabase.FindAssets("t:" + typeof(Settings).Name);
            if (guids.Length > 0)
            {
                string path = AssetDatabase.GUIDToAssetPath(guids[0]);
                return AssetDatabase.LoadAssetAtPath<Settings>(path);
            }
            return null;
        }
        #endif

    }
}
