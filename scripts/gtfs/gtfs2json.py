import sys
import json
import requests
from google.transit import gtfs_realtime_pb2
from google.protobuf.json_format import MessageToDict

if len(sys.argv) < 2:
    print("Usage: python gtfs2json.py <url>")
    sys.exit(1)

url = sys.argv[1]

resp = requests.get(url)
feed = gtfs_realtime_pb2.FeedMessage()
feed.ParseFromString(resp.content)

print(json.dumps(MessageToDict(feed)))
